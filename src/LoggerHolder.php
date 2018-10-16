<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Monolog\Logger;

class LoggerHolder
{

	/** @var Logger|null */
	private static $logger;

	/** @var LoggerHolder|null */
	private static $instSelf;

	/** @var Logger */
	private $instLogger;

	public function __construct(Logger $logger)
	{
		$this->instLogger = $logger;
	}

	public static function getInstance(): self
	{
		if (static::$logger === null) {
			throw new InvalidStateException(sprintf('Call %s::setLogger to use %s::getInstance', static::class, static::class));
		}

		if (static::$instSelf === null) {
			static::$instSelf = new static(static::$logger);
		}

		return static::$instSelf;
	}

	public static function setLogger(Logger $logger): void
	{
		static::$logger = $logger;
	}

	public function getLogger(): Logger
	{
		$backtrace = debug_backtrace();
		// Get class which called this or file if class does not exist
		$calledBy = $backtrace[1]['class'] ?? $backtrace[0]['file'];

		$logger = clone $this->instLogger;

		// Write in log which class used LoggerHolder
		$logger->pushProcessor(function (array $record) use ($calledBy): array {
			$record['extra']['calledBy'] = $calledBy;
			return $record;
		});

		return $logger;
	}

}
