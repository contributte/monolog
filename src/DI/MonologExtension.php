<?php declare(strict_types = 1);

namespace Contributte\Monolog\DI;

use Contributte\Monolog\Exception\Logic\InvalidArgumentException;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Strings;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;
use Tracy\Debugger;

class MonologExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'channel' => Expect::arrayOf(Expect::structure([
				'handlers'   => Expect::array()->required()->min(1),
				'processors' => Expect::array()->default([]),
			])->castTo('array'))->required()->min(1),
			'hook'    => Expect::structure([
				'fromTracy' => Expect::bool()->default(true),
				'toTracy'   => Expect::bool()->default(true),
			])->castTo('array'),
			'holder'  => Expect::structure([
				'enabled' => Expect::bool()->default(false),
			])->castTo('array'),
			'manager' => Expect::structure([
				'enabled' => Expect::bool()->default(false),
			])->castTo('array'),
		])->castTo('array');
	}

	public function loadConfiguration(): void
	{
		$config = (array) $this->getConfig();
		$builder = $this->getContainerBuilder();

		if (!isset($config['channel']['default'])) {
			throw new InvalidStateException(sprintf('%s.channel.default is required.', $this->name));
		}

		if ($config['manager']['enabled']) {
			$builder->addDefinition($this->prefix('manager'))
				->setFactory(LoggerManager::class, [
					$this->prefix('logger'),
				]);
		}

		$tracyHandler = null;

		if (class_exists(Debugger::class) && $config['hook']['toTracy'] && $builder->hasDefinition('tracy.logger')) {
			$tracyAdapter = new Statement(TracyToPsrLoggerAdapter::class, ['@tracy.logger']);
			$tracyHandler = new Statement(PsrHandler::class, [$tracyAdapter]);
		}

		foreach ($config['channel'] as $name => $channel) {
			if (!is_string($name)) {
				throw new InvalidArgumentException(sprintf('%s.channel.%s name must be a string', $this->name, (string) $name));
			}

			if ($tracyHandler !== null) {
				$channel['handlers']['tracy'] = $tracyHandler;
			}

			// Register handlers same way as services (setup, arguments, type etc.)
			foreach ($channel['handlers'] as $handlerKey => $handlerValue) {
				// Don't register handler as service, it's already registered service
				if (is_string($handlerValue) && Strings::startsWith($handlerValue, '@')) {
					continue;
				}

				$handlerName = $this->prefix('logger.' . $name . '.handler.' . $handlerKey);
				$this->compiler->loadDefinitionsFromConfig([$handlerName => $handlerValue]);
				$builder->getDefinition($handlerName)->setAutowired(false);
				$channel['handlers'][$handlerKey] = '@' . $handlerName;
			}

			// Register processors same way as services (setup, arguments, type etc.)
			foreach ($channel['processors'] as $processorKey => $processorValue) {
				// Don't register processor as service, it's already registered service
				if (is_string($processorValue) && Strings::startsWith($processorValue, '@')) {
					continue;
				}

				$processorName = $this->prefix('logger.' . $name . '.processor.' . $processorKey);
				$this->compiler->loadDefinitionsFromConfig([$processorName => $processorValue]);
				$builder->getDefinition($processorName)->setAutowired(false);
				$channel['processors'][$processorKey] = '@' . $processorName;
			}

			$logger = $builder->addDefinition($this->prefix('logger.' . $name))
				->setFactory(Logger::class, [
					$name,
					$channel['handlers'],
					$channel['processors'],
				]);

			// Only default logger is autowired
			if ($name !== 'default') {
				$logger->setAutowired(false);
			}
		}

		if (class_exists(Debugger::class) && $config['hook']['fromTracy'] && $builder->hasDefinition('tracy.logger')) {
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
		$config = (array) $this->getConfig();
		$initialize = $class->getMethod('initialize');

		if (class_exists(Debugger::class) && $config['hook']['fromTracy'] && $builder->hasDefinition('tracy.logger')) {
			$initialize->addBody('$this->getService("tracy.logger");'); // Create original Tracy\Logger service to prevent psrToTracyLazyAdapter contain itself - workaround for Tracy\ILogger service created statically
			$initialize->addBody(Debugger::class . '::setLogger($this->getService(?));', [$this->prefix('psrToTracyLazyAdapter')]);
		}

		if ($config['holder']['enabled']) {
			$initialize->addBody(LoggerHolder::class . '::setLogger(?, $this);', [$this->prefix('logger.default')]);
		}
	}

}
