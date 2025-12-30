<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Tester\Environment;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tracy\Bridges\Nette\TracyExtension;

final class Helpers
{

	public static function createContainer(string $configFile): Container
	{
		$loader = new ContainerLoader(Environment::getTestDir(), true);
		$class = $loader->load(static function (Compiler $compiler) use ($configFile): void {
			$compiler->loadConfig($configFile);
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('monolog', new MonologExtension());
		}, random_bytes(10));

		return new $class();
	}

}
