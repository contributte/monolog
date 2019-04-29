<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Psr\Log\LoggerInterface;

interface ILoggerManager
{

	public function has(string $name): bool;

	public function get(string $name): LoggerInterface;

}
