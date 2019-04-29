# Monolog

[Monolog](https://github.com/Seldaek/monolog/) integration into [Nette/DI](https://github.com/nette/di)

See also [Monolog documentation](https://github.com/Seldaek/monolog#documentation), this is only an integration.

## Setup

Install package

```bash
composer require contributte/monolog
```

Register extension

```yaml
extensions:
    monolog: Contributte\Monolog\DI\MonologExtension
```

## Configuration

```yaml
monolog:
    tracy:
        hook: true # use monolog inside tracy (required to log exceptions with monolog)
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
            processors:
                -  Monolog\Processor\MemoryPeakUsageProcessor()
```

## Logging

Log message with injected logger (only `default` is autowired)

```php
use Monolog\Logger;

class ExampleService
{

    /** @var Logger **/
    private $logger;

    public function injectLogger(Logger $logger): void
    {
        $this->logger = $logger;
        // or withName if you want change channel name
        $this->logger = $logger->withName('example');
    }

    public function doSomething(): void
    {
        $this->logger->info('Log that application did something');
    }

}
```

## LoggerManager

You could also use logger manager in case you need to use multiple logger at once.

```yaml
monolog:
    manager:
        enabled: false # disabled by default
        lazy: true # lazy by default
```

```php
use Contributte\Monolog\ILoggerManager;

class ExampleService
{

    /** @var ILoggerManager **/
    private $logger;

    public function injectLoggerManager(ILoggerManager $loggerManager): void
    {
        $this->loggerManager = $loggerManager;
    }

    public function doSomething(): void
    {
        $this->loggerManager->get('default')->info('Log that application did something');
        $this->loggerManager->get('specialLogger')->info('Log something very special')
    }

}
```

## LoggerHolder

Allow you get default logger statically in case that DIC is not available.

It add into message info about which class (or file) called LoggerHolder for easier debugging.

```yaml
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
