<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Nette\DI\Container;
use Psr\Log\LoggerInterface;

class LoggerManager
{

	/** @var string */
	private $prefix;

	/** @var Container */
	private $container;

	public function __construct(string $prefix, Container $container)
	{
		$this->prefix = $prefix;
		$this->container = $container;
	}

	public function has(string $name): bool
	{
		return $this->container->hasService(sprintf('%s.%s', $this->prefix, $name));
	}

	public function get(string $name): LoggerInterface
	{
		if (!$this->has($name)) {
			throw new InvalidStateException(sprintf('Cannot get undefined logger "%s".', $name));
		}

		return $this->container->getService(sprintf('%s.%s', $this->prefix, $name));
	}

}
