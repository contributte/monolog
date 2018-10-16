<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Monolog\Logger;

class LoggerManager implements ILoggerManager
{

	/** @var Logger[] */
	private $loggers;

	public function has(string $name): bool
	{
		return isset($this->loggers[$name]);
	}

	public function add(Logger $logger): void
	{
		$name = $logger->getName();

		if ($this->has($name)) {
			throw new InvalidStateException(sprintf('Cannot add logger with name "%s". Logger with same name is already defined.', $name));
		}

		$this->loggers[$name] = $logger;
	}

	public function get(string $name): Logger
	{
		if (!$this->has($name)) {
			throw new InvalidStateException(sprintf('Cannot get undefined logger "%s".', $name));
		}

		return $this->loggers[$name];
	}

}
