<?php declare(strict_types = 1);

use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\LoggerManager;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Contributte\Tester\Toolkit;
use Monolog\Logger;
use Nette\DI\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tests\Toolkit\Helpers;
use Tracy\ILogger;

require __DIR__ . '/../../bootstrap.php';

Toolkit::test(static function (): void {
	$container = Helpers::createContainer(__DIR__ . '/../../fixtures/config.neon');

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
});

Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_00.neon');
	}, InvalidConfigurationException::class, '~mandatory item .+monolog.+channel.+ is missing~');
});

Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_01.neon');
	}, InvalidConfigurationException::class, '~length of item .+monolog.+channel.+ expects to be in range 1~');
});

Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_02.neon');
	}, InvalidConfigurationException::class, '~mandatory item .+monolog.+channel.+default.+handlers.+ is missing~');
});

Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_03.neon');
	}, InvalidConfigurationException::class, '~length of item .+monolog.+channel.+default.+handlers.+ expects to be in range 1~');
});
