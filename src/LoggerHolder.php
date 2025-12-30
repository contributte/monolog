<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Monolog\Logger;
use Monolog\LogRecord;
use Nette\DI\Container;
use Psr\Log\LoggerInterface;

class LoggerHolder
{

	private static ?string $loggerServiceName = null;

	private static ?Container $container = null;

	/** @var static|null */
	private static $instSelf;

	private Logger $instLogger;

	final public function __construct(Logger $logger)
	{
		$this->instLogger = $logger;
	}

	/**
	 * @return static
	 */
	public static function getInstance(): self
	{
		if (self::$instSelf === null) {
			if (self::$loggerServiceName === null || self::$container === null) {
				throw new InvalidStateException(sprintf('Call %s::setLogger to use %s::getInstance', self::class, self::class));
			}

			/** @var Logger $logger */
			$logger = self::$container->getService(self::$loggerServiceName);
			self::$instSelf = new static($logger);
		}

		return self::$instSelf;
	}

	public static function setLogger(string $loggerServiceName, Container $container): void
	{
		self::$loggerServiceName = $loggerServiceName;
		self::$container = $container;
	}

	public function getLogger(): LoggerInterface
	{
		$backtrace = debug_backtrace();
		// Get class which called this or file if class does not exist
		$calledBy = $backtrace[1]['class'] ?? $backtrace[0]['file'] ?? $backtrace[0]['function'];

		$logger = clone $this->instLogger;

		// Write in log which class used LoggerHolder
		// Monolog >= 3.0
		if (class_exists(LogRecord::class)) {
			$logger->pushProcessor(function (LogRecord $record) use ($calledBy): LogRecord {
				$record->extra['calledBy'] = $calledBy;

				return $record;
			});
		} else {
			// @phpstan-ignore argument.type (Monolog 2.x compatibility)
			$logger->pushProcessor(function (array $record) use ($calledBy): array {
				$record['extra']['calledBy'] = $calledBy;

				return $record;
			});
		}

		return $logger;
	}

}
