<?php declare(strict_types = 1);

namespace Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Nette\DI\Container;
use Psr\Log\LoggerInterface;

class LazyLoggerManager implements ILoggerManager
{

	/** @var Container */
	private $container;

	/** @var string */
	private $prefix;

	public function __construct(Container $container, string $prefix)
	{
		$this->container = $container;
		$this->prefix = $prefix;
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
