# Contributte Monolog

Integration of [Monolog](https://github.com/Seldaek/monolog/) for Nette Framework.

## Content

- [Installation](#installation)
- [Configuration](#configuration)
  - [Channels](#channels)
  - [Handlers](#handlers)
  - [Processors](#processors)
  - [Tracy](#tracy)
- [Usage](#usage)
  - [Logging](#logging)
  - [LoggerManager](#loggermanager)
  - [LoggerHolder](#loggerholder)
- [Examples](#examples)

## Installation

Install package using composer.

```bash
composer require contributte/monolog
```

Register prepared [compiler extension](https://doc.nette.org/en/dependency-injection/nette-container) in your `config.neon` file.

```neon
extensions:
    monolog: Contributte\Monolog\DI\MonologExtension
```

> [!NOTE]
> This is just a Nette integration, see also [Monolog documentation](https://github.com/Seldaek/monolog#documentation) for more information about handlers, processors and formatters.

## Configuration

### Channels

You can configure multiple logging channels. The `default` channel is required and is the only one that is autowired.

```neon
monolog:
    channel:
        default:
            handlers:
                - Monolog\Handler\StreamHandler(%appDir%/../log/app.log)
        api:
            handlers:
                - Monolog\Handler\StreamHandler(%appDir%/../log/api.log)
```

### Handlers

Handlers are responsible for writing log records to various destinations. You can use any [Monolog handler](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#handlers).

```neon
monolog:
    channel:
        default:
            handlers:
                # Inline handler definition
                - Monolog\Handler\StreamHandler(%appDir%/../log/app.log, Monolog\Logger::WARNING)
                - Monolog\Handler\RotatingFileHandler(%appDir%/../log/app.log, 30, Monolog\Logger::DEBUG)
                # Reference to existing service
                - @myCustomHandler
```

### Processors

Processors allow you to add extra data to log records.

```neon
monolog:
    channel:
        default:
            handlers:
                - Monolog\Handler\StreamHandler(%appDir%/../log/app.log)
            processors:
                - Monolog\Processor\MemoryPeakUsageProcessor()
                - Monolog\Processor\WebProcessor()
                - Monolog\Processor\IntrospectionProcessor()
```

### Tracy

Monolog integrates with Tracy debugger. By default, logs are bridged in both directions.

```neon
monolog:
    hook:
        fromTracy: true # enabled by default, log Tracy messages to Monolog
        toTracy: true   # enabled by default, log Monolog messages to Tracy
```

> [!TIP]
> For remote storage of Tracy bluescreens, consider using [mangoweb-backend/monolog-tracy-handler](https://github.com/mangoweb-backend/monolog-tracy-handler).

## Usage

### Logging

Inject the logger using constructor injection or `inject*` method. Only the `default` channel is autowired.

```php
use Psr\Log\LoggerInterface;

class OrderService
{

    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    public function process(): void
    {
        $this->logger->info('Processing order');
        $this->logger->error('Order failed', ['orderId' => 123]);
    }

}
```

### LoggerManager

Use LoggerManager when you need to access multiple channels.

```neon
monolog:
    manager:
        enabled: true # disabled by default
```

```php
use Contributte\Monolog\LoggerManager;

class ReportService
{

    public function __construct(
        private LoggerManager $loggerManager,
    )
    {
    }

    public function generate(): void
    {
        $this->loggerManager->get('default')->info('Generating report');
        $this->loggerManager->get('api')->info('Fetching data from API');
    }

}
```

### LoggerHolder

LoggerHolder provides static access to the default logger when DI container is not available.

```neon
monolog:
    holder:
        enabled: true # disabled by default
```

```php
use Contributte\Monolog\LoggerHolder;

class LegacyCode
{

    public function process(): void
    {
        LoggerHolder::getInstance()->getLogger()->info('Processing');
    }

}
```

> [!WARNING]
> LoggerHolder should only be used in legacy code or situations where dependency injection is not possible.

## Examples

### Full configuration

```neon
monolog:
    channel:
        default:
            handlers:
                - Monolog\Handler\RotatingFileHandler(%appDir%/../log/app.log, 14, Monolog\Logger::DEBUG)
                - Monolog\Handler\StreamHandler(php://stderr, Monolog\Logger::ERROR)
            processors:
                - Monolog\Processor\MemoryPeakUsageProcessor()
                - Monolog\Processor\WebProcessor()

        email:
            handlers:
                - Monolog\Handler\StreamHandler(%appDir%/../log/email.log)

    hook:
        fromTracy: true
        toTracy: true

    manager:
        enabled: true

    holder:
        enabled: false
```

### Custom handler service

```neon
services:
    slackHandler:
        factory: Monolog\Handler\SlackWebhookHandler(
            %slack.webhookUrl%,
            %slack.channel%,
            %slack.username%
        )

monolog:
    channel:
        default:
            handlers:
                - Monolog\Handler\StreamHandler(%appDir%/../log/app.log)
                - @slackHandler
```

> [!TIP]
> Take a look at real **Contributte Monolog** configuration example at [contributte/webapp-skeleton](https://github.com/contributte/webapp-skeleton).
