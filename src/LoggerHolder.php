<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Monolog\Logger;
use Nette\DI\Container;
use Psr\Log\LoggerInterface;

class LoggerHolder
{

	/** @var string|null */
	private static $loggerServiceName;

	/** @var Container|null */
	private static $container;

	/** @var static|null */
	private static $instSelf;

	/** @var Logger */
	private $instLogger;

	public static function setLogger(string $loggerServiceName, Container $container): void
	{
		static::$loggerServiceName = $loggerServiceName;
		static::$container = $container;
	}

	/**
	 * @return static
	 */
	public static function getInstance(): self
	{
		if (static::$instSelf === null) {
			if (static::$loggerServiceName === null || static::$container === null) {
				throw new InvalidStateException(sprintf('Call %s::setLogger to use %s::getInstance', static::class, static::class));
			}

			/** @var Logger $logger */
			$logger = static::$container->getService(static::$loggerServiceName);
			static::$instSelf = new static($logger);
		}

		return static::$instSelf;
	}

	final public function __construct(Logger $logger)
	{
		$this->instLogger = $logger;
	}

	public function getLogger(): LoggerInterface
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
