<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Monolog\Logger;

interface ILoggerManager
{

	public function has(string $name): bool;

	public function get(string $name): Logger;

}
