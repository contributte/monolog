<?php declare(strict_types = 1);

namespace Contributte\Monolog\DI\Helpers;

use Contributte\Monolog\Exception\Logic\InvalidArgumentException;
use Nette\DI\Definitions\Statement;

final class SmartStatement
{

	public static function from(mixed $service): Statement|string
	{
		if (is_string($service) && str_starts_with($service, '@')) {
			return $service;
		}

		if (is_string($service)) {
			return new Statement($service);
		}

		if ($service instanceof Statement) {
			return $service;
		}

		throw new InvalidArgumentException('Unsupported type of service');
	}

}
