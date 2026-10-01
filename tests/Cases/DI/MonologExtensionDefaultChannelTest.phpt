<?php declare(strict_types = 1);

use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerHolder;
use Contributte\Monolog\Tracy\LazyTracyLogger;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\ILogger;

require __DIR__ . '/../../bootstrap.php';

// Custom default channel is autowired and used by LoggerHolder
Toolkit::test(static function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('monolog', new MonologExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					testHandler: Monolog\Handler\TestHandler

				monolog:
					defaultChannel: app
					channel:
						app:
							handlers:
								- @testHandler
						foo:
							handlers:
								- Monolog\Handler\NullHandler
					holder:
						enabled: true
					hook:
						toTracy: false
			NEON));
		})
		->build();

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
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('monolog', new MonologExtension());
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					monolog:
						defaultChannel: app
						channel:
							default:
								handlers:
									- Monolog\Handler\NullHandler
				NEON));
			})
			->build();
	}, InvalidStateException::class, 'monolog.channel.app is required.');
});

// Configured default channel must not be empty
Toolkit::test(static function (): void {
	Assert::exception(static function (): void {
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('monolog', new MonologExtension());
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					monolog:
						defaultChannel: ''
						channel:
							default:
								handlers:
									- Monolog\Handler\NullHandler
				NEON));
			})
			->build();
	}, InvalidConfigurationException::class, '~length of item .+monolog.+defaultChannel.+ expects to be in range 1~');
});
