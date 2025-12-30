<?php declare(strict_types = 1);

namespace Contributte\Monolog\Tracy;

use Nette\DI\Container;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\ILogger;

class LazyTracyLogger implements ILogger
{

	private string $loggerServiceName;

	private Container $container;

	private ?PsrToTracyLoggerAdapter $internalLogger = null;

	public function __construct(string $loggerServiceName, Container $container)
	{
		$this->loggerServiceName = $loggerServiceName;
		$this->container = $container;
	}

	public function log(mixed $value, mixed $priority = self::INFO): void
	{
		if ($this->internalLogger === null) {
			$this->internalLogger = $this->container->getService($this->loggerServiceName);
		}

		$this->internalLogger->log($value, $priority);
	}

}
