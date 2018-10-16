<?php declare(strict_types = 1);

namespace Contributte\Monolog\DI;

use Contributte\Monolog\Exception\Logic\InvalidArgumentException;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LazyLoggerManager;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\TracyAdapter;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Strings;
use Tracy\Debugger;

class MonologExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'channel' => [],
		'tracy' => [
			'hook' => true,
		],
		'holder' => [
			'enabled' => false,
		],
		'manager' => [
			'enabled' => false,
			'lazy' => true,
		],
	];

	/** @var mixed [] */
	private $channelDefaults = [
		'handlers' => [],
		'processors' => [],
	];

	public function loadConfiguration(): void
	{
		$config = $this->validateConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		if (!isset($config['channel']['default'])) {
			throw new InvalidStateException(sprintf('%s.channel.default is required.', $this->name));
		}

		if ($config['manager']['enabled']) {
			$manager = $builder->addDefinition($this->prefix('manager'));
			if ($config['manager']['lazy']) {
				$manager->setFactory(LazyLoggerManager::class, ['prefix' => $this->prefix('logger')]);
			} else {
				$manager->setFactory(LoggerManager::class);
			}
		}

		foreach ($config['channel'] as $name => $channel) {
			$channel = $this->validateConfig($this->channelDefaults, $channel, $this->prefix('channel.' . $name));

			if (!is_string($name)) {
				throw new InvalidArgumentException(sprintf('%s.channel.%s name must be a string', $this->name, (string) $name));
			}

			if (!isset($channel['handlers']) || $channel['handlers'] === []) {
				throw new InvalidStateException(sprintf('%s.channel.%s.handlers must contain at least one handler', $this->name, $name));
			}

			// Register handlers same way as services (setup, arguments, type etc.)
			foreach ($channel['handlers'] as $handlerKey => $handlerValue) {
				// Don't register handler as service, it's already registered service
				if (is_string($handlerValue) && Strings::startsWith($handlerValue, '@')) {
					continue;
				}

				$handlerName = $this->prefix('logger.' . $name . '.handler.' . $handlerKey);
				$handler = $builder->addDefinition($handlerName)
					->setAutowired(false);

				Compiler::loadDefinition($handler, $handlerValue);
				$channel['handlers'][$handlerKey] = '@' . $handlerName;
			}

			// Register processors same way as services (setup, arguments, type etc.)
			if (isset($channel['processors'])) {
				foreach ($channel['processors'] as $processorKey => $processorValue) {
					// Don't register processor as service, it's already registered service
					if (is_string($processorValue) && Strings::startsWith($processorValue, '@')) {
						continue;
					}

					$processorName = $this->prefix('logger.' . $name . '.processor.' . $processorKey);
					$processor = $builder->addDefinition($processorName)
						->setAutowired(false);

					Compiler::loadDefinition($processor, $processorValue);
					$channel['processors'][$processorKey] = '@' . $processorName;
				}
			}

			$logger = $builder->addDefinition($this->prefix('logger.' . $name))
				->setType(Logger::class)
				->setArguments([
					$name,
					$channel['handlers'],
					$channel['processors'] ?? [],
				]);

			if ($config['manager']['enabled'] === true && $config['manager']['lazy'] !== true) {
				$manager->addSetup('add', [$logger]);
			}

			// Only default logger is autowired
			if ($name !== 'default') {
				$logger->setAutowired(false);
			}
		}

		if ($config['tracy']['hook'] === true && class_exists(Debugger::class)) {
			if ($builder->hasDefinition('tracy.logger')) {
				$builder->removeDefinition('tracy.logger');
				$builder->addAlias('tracy.logger', $this->prefix('tracyAdapter'));
			}

			$builder->addDefinition($this->prefix('tracyAdapter'))
				->setFactory(TracyAdapter::class, [$this->prefix('@logger.default'), Debugger::$logDirectory, Debugger::$email]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$config = $this->validateConfig($this->defaults);
		if ($config['holder']['enabled']) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody('Contributte\Monolog\LoggerHolder::setLogger($this->getByType(?));', [Logger::class]);
		}
	}

}
