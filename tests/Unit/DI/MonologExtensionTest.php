<?php declare(strict_types = 1);

namespace Tests\Contributte\Monolog\Unit\DI;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerManager;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
				manager:
					enabled: true
		'));

		/** @var Logger $default */
		$default = $container->getByType(LoggerInterface::class);
		$this->assertInstanceOf(Logger::class, $default);
		$this->assertEquals('default', $default->getName());

		/** @var Logger $foo */
		$foo = $container->getService('monolog.logger.foo');
		$this->assertInstanceOf(LoggerInterface::class, $foo);
		$this->assertEquals('foo', $foo->getName());

		$this->assertInstanceOf(Logger::class, $container->getByType(Logger::class));

		/** @var LoggerManager $manager */
		$manager = $container->getByType(LoggerManager::class);

		$this->assertTrue($manager->has('default'));
		$this->assertSame($default, $manager->get('default'));

		$this->assertTrue($manager->has('foo'));
		$this->assertSame($foo, $manager->get('foo'));
	}

	public function testRegistrationNoDefault(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('monolog.channel.default is required.');

		$container = $this->createContainer([]);
	}

	/**
	 * @param mixed[] $config
	 */
	private function createContainer(array $config): Container
	{
		$loader = new ContainerLoader(__DIR__ . '/../../../temp/tests/' . getmypid(), true);
		$class = $loader->load(function (Compiler $compiler) use ($config): void {
			$compiler->addConfig($config);
			$compiler->addExtension('monolog', new MonologExtension());
		}, serialize($config));

		/** @var Container $container */
		return new $class();
	}

}
