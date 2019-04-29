<?php declare(strict_types = 1);

namespace Tests\Contributte\Monolog\DI;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use PHPUnit\Framework\TestCase;
use Tests\Contributte\Monolog\NeonLoader;

class MonologExtensionTest extends TestCase
{

	public function testRegistration(): void
	{
		$container = $this->createContainer(NeonLoader::load('
			monolog:
				channel:
					default:
						handlers:
							- Monolog\Handler\NullHandler
					foo:
						handlers:
							- Monolog\Handler\NullHandler
		'));

		/** @var Logger $default */
		$default = $container->getByType(Logger::class);
		$this->assertInstanceOf(Logger::class, $default);
		$this->assertEquals('default', $default->getName());

		/** @var Logger $foo */
		$foo = $container->getService('monolog.logger.foo');
		$this->assertInstanceOf(Logger::class, $foo);
		$this->assertEquals('foo', $foo->getName());
	}

	public function testRegistrationNoDefault(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('monolog.channel.default is required.');

		$container = $this->createContainer([]);

		$container->getByType(Logger::class);
	}

	/**
	 * @param mixed[] $config
	 */
	private function createContainer(array $config): Container
	{
		$loader = new ContainerLoader(__DIR__ . '/../../temp/tests/' . getmypid(), true);
		$class = $loader->load(function (Compiler $compiler) use ($config): void {
			$compiler->addConfig($config);
			$compiler->addExtension('monolog', new MonologExtension());
		}, serialize($config));

		/** @var Container $container */
		return new $class();
	}

}
