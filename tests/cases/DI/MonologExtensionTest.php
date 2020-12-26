<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\DI\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\ILogger;

require __DIR__ . '/../../bootstrap.php';

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
		Assert::type(Logger::class, $default);
		Assert::equal('default', $default->getName());

		/** @var Logger $foo */
		$foo = $container->getService('monolog.logger.foo');
		Assert::type(LoggerInterface::class, $foo);
		Assert::equal('foo', $foo->getName());

		Assert::type(Logger::class, $container->getByType(Logger::class));

		/** @var LoggerManager $manager */
		$manager = $container->getByType(LoggerManager::class);

		Assert::true($manager->has('default'));
		Assert::same($default, $manager->get('default'));

		Assert::true($manager->has('foo'));
		Assert::same($foo, $manager->get('foo'));

		Assert::type(LoggerInterface::class, LoggerHolder::getInstance()->getLogger());

		Assert::type(LazyTracyLogger::class, $container->getByType(ILogger::class));
	}

	public function testRegistrationNoDefault(): void
	{
		Assert::exception(function (): void {
			$this->createContainer(self::FIXTURES_DIR . '/config_00.neon');
		}, InvalidConfigurationException::class, "The mandatory item 'monolog › channel' is missing.");
	}

	public function testRegistrationEmptyChannels(): void
	{
		Assert::exception(function (): void {
			$this->createContainer(self::FIXTURES_DIR . '/config_01.neon');
		}, InvalidConfigurationException::class, "The length of item 'monolog › channel' expects to be in range 1.., 0 items given.");
	}

	public function testRegistrationEmptyChannel(): void
	{
		Assert::exception(function (): void {
			$this->createContainer(self::FIXTURES_DIR . '/config_02.neon');
		}, InvalidConfigurationException::class, "The mandatory item 'monolog › channel › default › handlers' is missing.");
	}

	public function testRegistrationEmptyHandlers(): void
	{
		Assert::exception(function (): void {
			$this->createContainer(self::FIXTURES_DIR . '/config_03.neon');
		}, InvalidConfigurationException::class, "The length of item 'monolog › channel › default › handlers' expects to be in range 1.., 0 items given.");
	}

	private function createContainer(string $configFile): Container
	{
		$loader = new ContainerLoader(TEMP_DIR, true);
		$class = $loader->load(static function (Compiler $compiler) use ($configFile): void {
			$compiler->loadConfig($configFile);
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('monolog', new MonologExtension());
		}, random_bytes(10));

		return new $class();
	}

}

(new MonologExtensionTest())->run();
