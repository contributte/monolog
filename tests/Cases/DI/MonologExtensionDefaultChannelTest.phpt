<?php declare(strict_types = 1);

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Tester\Toolkit;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tests\Toolkit\Helpers;

require __DIR__ . '/../../bootstrap.php';

// Custom default channel is autowired and used by LoggerHolder
Toolkit::test(static function (): void {
	$container = Helpers::createContainer(__DIR__ . '/../../fixtures/config_04.neon');

	// Needed for LoggerHolder
	$container->initialize();

	/** @var Logger $app */
	$app = $container->getByType(LoggerInterface::class);
	Assert::type(Logger::class, $app);
	Assert::equal('app', $app->getName());
	Assert::same($app, $container->getService('monolog.logger.app'));

	// Only the configured default channel is autowired
	Assert::same($app, $container->getByType(Logger::class));
	Assert::false($container->hasService('monolog.logger.default'));

	/** @var Logger $foo */
	$foo = $container->getService('monolog.logger.foo');
	Assert::equal('foo', $foo->getName());
	Assert::notSame($foo, $app);

	/** @var Logger $holderLogger */
	$holderLogger = LoggerHolder::getInstance()->getLogger();
	Assert::equal('app', $holderLogger->getName());
});

// Configured default channel must exist
Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_05.neon');
	}, InvalidStateException::class, 'monolog.channel.app is required.');
});
