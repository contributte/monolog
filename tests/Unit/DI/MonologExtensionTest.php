<?php declare(strict_types = 1);

namespace Tests\Contributte\Monolog\Unit\DI;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\DI\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\ILogger;

class MonologExtensionTest extends TestCase
{

	private const FIXTURES_DIR = __DIR__ . '/../../fixtures';

	public function testRegistration(): void
	{
		$container = $this->createContainer(self::FIXTURES_DIR . '/config.neon');

		// Needed for LoggerHolder and creation of original Tracy\Logger
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

		$this->assertInstanceOf(LazyTracyLogger::class, $container->getByType(ILogger::class));
	}

	public function testRegistrationNoDefault(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectExceptionMessage('The mandatory option \'monolog › channel\' is missing.');

		$container = $this->createContainer(self::FIXTURES_DIR . '/config_00.neon');
	}

	public function testRegistrationEmptyChannels(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectExceptionMessage('The option \'monolog › channel\' expects to be array in range 1.., array given.');

		$container = $this->createContainer(self::FIXTURES_DIR . '/config_01.neon');
	}

	public function testRegistrationEmptyChannel(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectExceptionMessage('The mandatory option \'monolog › channel › default › handlers\' is missing.');

		$container = $this->createContainer(self::FIXTURES_DIR . '/config_02.neon');
	}

	public function testRegistrationEmptyHandlers(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectExceptionMessage('The option \'monolog › channel › default › handlers\' expects to be array in range 1.., array given.');

		$container = $this->createContainer(self::FIXTURES_DIR . '/config_03.neon');
	}

	private function createContainer(string $configFile): Container
	{
		$loader = new ContainerLoader(__DIR__ . '/../../../temp/tests/' . getmypid(), true);
		$class = $loader->load(static function (Compiler $compiler) use ($configFile): void {
			$compiler->loadConfig($configFile);
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('monolog', new MonologExtension());
		}, random_bytes(10));

		/** @var Container $container */
		return new $class();
	}

}
