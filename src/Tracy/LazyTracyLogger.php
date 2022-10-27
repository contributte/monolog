<?php declare(strict_types = 1);

namespace Contributte\Monolog\Tracy;

use Nette\DI\Container;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\ILogger;

class LazyTracyLogger implements ILogger
{

	/** @var string */
	private $loggerServiceName;

	/** @var Container */
	private $container;

	/** @var PsrToTracyLoggerAdapter|null */
	private $internalLogger;

	public function __construct(string $loggerServiceName, Container $container)
	{
		$this->loggerServiceName = $loggerServiceName;
		$this->container = $container;
	}

	/**
	 * @param mixed $value
	 * @param mixed $priority
	 */
	public function log($value, $priority = self::INFO): void
	{
		if ($this->internalLogger === null) {
			$this->internalLogger = $this->container->getService($this->loggerServiceName);
		}

		$this->internalLogger->log($value, $priority);
	}

}
