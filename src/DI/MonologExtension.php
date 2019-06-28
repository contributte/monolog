<?php declare(strict_types = 1);

namespace Contributte\Monolog\DI;

use Contributte\DI\Helper\ExtensionDefinitionsHelper;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;
use Tracy\Debugger;

/**
 * @property-read stdClass $config
 */
class MonologExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'channel' => Expect::arrayOf(Expect::structure([
				'handlers' => Expect::arrayOf(
					Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
				)->required()->min(1),
				'processors' => Expect::arrayOf(
					Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
				),
			]))->required()->min(1),
			'hook' => Expect::structure([
				'fromTracy' => Expect::bool(true),
				'toTracy' => Expect::bool(true),
			]),
			'holder' => Expect::structure([
				'enabled' => Expect::bool(false),
			]),
			'manager' => Expect::structure([
				'enabled' => Expect::bool(false),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$config = $this->config;
		$builder = $this->getContainerBuilder();
		$definitionsHelper = new ExtensionDefinitionsHelper($this->compiler);

		if (!isset($config->channel['default'])) {
			throw new InvalidStateException(sprintf('%s.channel.default is required.', $this->name));
		}

		if ($config->manager->enabled) {
			$builder->addDefinition($this->prefix('manager'))
				->setFactory(LoggerManager::class, [
					$this->prefix('logger'),
				]);
		}

		$tracyHandler = null;

		if ($config->hook->toTracy && class_exists(Debugger::class) && $builder->hasDefinition('tracy.logger')) {
			$tracyAdapter = new Statement(TracyToPsrLoggerAdapter::class, ['@tracy.logger']);
			$tracyHandler = new Statement(PsrHandler::class, [$tracyAdapter]);
		}

		foreach ($config->channel as $name => $channel) {
			$name = (string) $name;

			if ($tracyHandler !== null) {
				$channel->handlers['tracy'] = $tracyHandler;
			}

			// Register handlers
			$handlerDefinitions = [];

			foreach ($channel->handlers as $handlerName => $handlerConfig) {
				$handlerPrefix = $this->prefix('logger.' . $name . '.handler.' . $handlerName);
				$handlerDefinition = $definitionsHelper->getDefinitionFromConfig($handlerConfig, $handlerPrefix);

				if ($handlerDefinition instanceof Definition) {
					$handlerDefinition->setAutowired(false);
				}

				$handlerDefinitions[] = $handlerDefinition;
			}

			// Register processors
			$processorDefinitions = [];

			foreach ($channel->processors as $processorName => $processorConfig) {
				$processorPrefix = $this->prefix('logger.' . $name . '.processor.' . $processorName);
				$processorDefinition = $definitionsHelper->getDefinitionFromConfig($processorConfig, $processorPrefix);

				if ($processorDefinition instanceof Definition) {
					$processorDefinition->setAutowired(false);
				}

				$processorDefinitions[] = $processorDefinition;
			}

			$logger = $builder->addDefinition($this->prefix('logger.' . $name))
				->setFactory(Logger::class, [
					$name,
					$handlerDefinitions,
					$processorDefinitions,
				]);

			// Only default logger is autowired
			if ($name !== 'default') {
				$logger->setAutowired(false);
			}
		}

		if ($config->hook->fromTracy && class_exists(Debugger::class) && $builder->hasDefinition('tracy.logger')) {
			$builder->getDefinition('tracy.logger')
				->setAutowired(false);

			$builder->addDefinition($this->prefix('psrToTracyAdapter'))
				->setFactory(PsrToTracyLoggerAdapter::class)
				->setAutowired(false);

			$builder->addDefinition($this->prefix('psrToTracyLazyAdapter'))
				->setFactory(LazyTracyLogger::class, [$this->prefix('psrToTracyAdapter')]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$initialize = $class->getMethod('initialize');

		if ($config->hook->fromTracy && class_exists(Debugger::class) && $builder->hasDefinition('tracy.logger')) {
			$initialize->addBody('$this->getService("tracy.logger");'); // Create original Tracy\Logger service to prevent psrToTracyLazyAdapter contain itself - workaround for Tracy\ILogger service created statically
			$initialize->addBody(Debugger::class . '::setLogger($this->getService(?));', [$this->prefix('psrToTracyLazyAdapter')]);
		}

		if ($config->holder->enabled) {
			$initialize->addBody(LoggerHolder::class . '::setLogger(?, $this);', [$this->prefix('logger.default')]);
		}
	}

}
