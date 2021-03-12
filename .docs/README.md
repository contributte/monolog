# Contributte Monolog

[Monolog](https://github.com/Seldaek/monolog/) integration into [Nette/DI](https://github.com/nette/di)

See also [Monolog documentation](https://github.com/Seldaek/monolog#documentation), this is only an integration.

## Content

- [Setup](#setup)
- [Configuration](#configuration)
	- [Tracy](#tracy)
- [Logging](#logging)
- [Logger manager](#loggermanager)
- [Logger holder](#loggerholder)

## Setup

Install package

```bash
composer require contributte/monolog
```

Register extension

```neon
extensions:
	monolog: Contributte\Monolog\DI\MonologExtension
```

## Configuration

```neon
monolog:
	channel:
		default: # default channel is required
			handlers:
				- Monolog\Handler\RotatingFileHandler(%appDir%/../log/syslog.log, 30, Monolog\Logger::WARNING)
				# you can use same configuration as in services section (with setup, type, arguments, etc.)
				-
					type: Monolog\Handler\RotatingFileHandler
					arguments:
						- %appDir%/../log/syslog.log
						- 30
						- Monolog\Logger::WARNING
				- @serviceName # or reference an existing service
			processors:
				-  Monolog\Processor\MemoryPeakUsageProcessor()
```

### Tracy

```neon
monolog:
	hook:
		fromTracy: true # enabled by default, log through Tracy into Monolog
		toTracy: true # enabled by default, log through Monolog into Tracy
```

You may also want configure remote storage for Tracy bluescreens. In this case use [mangoweb-backend/monolog-tracy-handler](https://github.com/mangoweb-backend/monolog-tracy-handler)

## Logging

Log message with injected logger (only `default` is autowired)

```php
use Psr\Log\LoggerInterface;

class ExampleService
{

	/** @var LoggerInterface **/
	private $logger;

	public function injectLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	public function doSomething(): void
	{
		$this->logger->info('Log that application did something');
	}

}
```

## LoggerManager

You could also use logger manager in case you need to use multiple logger at once.

```neon
monolog:
	manager:
		enabled: false # disabled by default
```

```php
use Contributte\Monolog\LoggerManager;

class ExampleService
{

	/** @var LoggerManager **/
	private $loggerManager;

	public function injectLoggerManager(LoggerManager $loggerManager): void
	{
		$this->loggerManager = $loggerManager;
	}

	public function doSomething(): void
	{
		$this->loggerManager->get('default')->info('Log that application did something');
		$this->loggerManager->get('specialLogger')->info('Log something very special');
	}

}
```

## LoggerHolder

Allow you get default logger statically in case that DIC is not available.

It add into message info about which class (or file) called LoggerHolder for easier debugging.

```neon
monolog:
	holder:
		enabled: false # disabled by default
```

```php
use Contributte\Monolog\LoggerHolder;

class VerySpecialClassWithoutDependencyInjectionContainerAvailable
{

	public function doSomething(): void
	{
		LoggerHolder::getInstance()->getLogger()->info('Log that application did something');
	}

}
```
