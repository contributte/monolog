<?php declare(strict_types = 1);

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Contributte\Tester\Toolkit;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nette\DI\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tests\Toolkit\Helpers;
use Tracy\ILogger;

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

	// Tracy logs are passed to the custom default channel
	$tracyLogger = $container->getByType(ILogger::class);
	Assert::type(LazyTracyLogger::class, $tracyLogger);
	$tracyLogger->log('Tracy message', ILogger::ERROR);

	/** @var TestHandler $testHandler */
	$testHandler = $container->getService('testHandler');
	Assert::true($testHandler->hasErrorThatContains('Tracy message'));
});

// Configured default channel must exist
Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_05.neon');
	}, InvalidStateException::class, 'monolog.channel.app is required.');
});

// Configured default channel must not be empty
Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		Helpers::createContainer(__DIR__ . '/../../fixtures/config_06.neon');
	}, InvalidConfigurationException::class, '~length of item .+monolog.+defaultChannel.+ expects to be in range 1~');
});
