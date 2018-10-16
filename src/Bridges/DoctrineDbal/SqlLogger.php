<?php declare(strict_types = 1);

namespace Contributte\Monolog\Bridges\DoctrineDbal;

use Doctrine\DBAL\Logging\SQLLogger as DoctrineSQLLogger;
use Monolog\Logger;

class SqlLogger implements DoctrineSQLLogger
{

	/** @var Logger */
	private $logger;

	/** @var float */
	private $startTime;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param string       $sql
	 * @param mixed[]|null $params
	 * @param mixed[]|null $types
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		$sql = 'Query: ' . $sql;
		$this->logger->debug($sql);
		$this->startTime = microtime(true);
	}

	public function stopQuery(): void
	{
		$ms = round((microtime(true) - $this->startTime) * 1000);
		$this->logger->debug(sprintf('Query took %s ms.', $ms));
	}

}
