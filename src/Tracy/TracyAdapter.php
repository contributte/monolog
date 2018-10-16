<?php declare(strict_types = 1);

namespace Contributte\Monolog\Tracy;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Tracy\BlueScreen;
use Tracy\Dumper;
use Tracy\ILogger;
use Tracy\Logger;

class TracyAdapter extends Logger
{

	private const PRIORITY_MAP = [
		ILogger::DEBUG => LogLevel::DEBUG,
		ILogger::INFO => LogLevel::INFO,
		ILogger::WARNING => LogLevel::WARNING,
		ILogger::ERROR => LogLevel::ERROR,
		ILogger::EXCEPTION => LogLevel::ERROR,
		ILogger::CRITICAL => LogLevel::CRITICAL,
	];

	/** @var LoggerInterface */
	private $psrLogger;

	/**
	 * @param string|null          $directory
	 * @param string|string[]|null $email
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function __construct(LoggerInterface $psrLogger, $directory, $email = null, ?BlueScreen $blueScreen = null)
	{
		parent::__construct($directory, $email, $blueScreen);
		$this->psrLogger = $psrLogger;
	}

	/**
	 * @inheritdoc
	 */
	public function log($message, $priority = ILogger::INFO): ?string
	{
		$this->psrLog($message, $priority);
		return parent::log($message, $priority);
	}

	/**
	 * @param mixed $value
	 */
	private function psrLog($value, string $priority): void
	{
		if ($value instanceof Throwable) {
			$message = $value->getMessage();
			$context = ['exception' => $value];
		} elseif (!is_string($value)) {
			$message = trim(Dumper::toText($value));
			$context = [];
		} else {
			$message = $value;
			$context = [];
		}
		$this->psrLogger->log(
			self::PRIORITY_MAP[$priority] ?? LogLevel::ERROR,
			$message,
			$context
		);
	}

}
