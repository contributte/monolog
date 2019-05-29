<?php declare(strict_types = 1);

namespace Tests\Contributte\Monolog\Unit\DI;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MonologExtensionTest extends TestCase
{

	public function testRegistration(): void
	{
		$container = $this->createContainer(__DIR__ . '/config.neon');

		// Needed for LoggerHolder
		$container->initialize();

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

		$this->assertInstanceOf(LoggerInterface::class, LoggerHolder::getInstance()->getLogger());
	}

	public function testRegistrationNoDefault(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('monolog.channel.default is required.');

		$container = $this->createContainer(__DIR__ . '/empty.neon');
	}

	private function createContainer(string $configFile): Container
	{
		$loader = new ContainerLoader(__DIR__ . '/../../../temp/tests/' . getmypid(), true);
		$class = $loader->load(function (Compiler $compiler) use ($configFile): void {
			$compiler->loadConfig($configFile);
			$compiler->addExtension('monolog', new MonologExtension());
		}, random_bytes(10));

		/** @var Container $container */
		return new $class();
	}

}
