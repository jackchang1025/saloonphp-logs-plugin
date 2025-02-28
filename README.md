# SaloonPHP Logs Plugin

## Installation

Install SaloonPHP Logs Plugin using Composer:

```bash
composer require weijiajia/saloonphp-logs-plugin
```

## Usage Examples

1. Create an ExampleConnector instance:

```php

use Weijiajia\SaloonphpLogsPlugin\Traits\HasLogger;

class ExampleConnector extends Connector
{
    use HasLogger;

    // set default log
    public function getLogger(): ?LoggerInterface
    {
        return new Logger('saloonphp-logs-plugin');
    }

    // custom format request log
    protected function formatRequestLog(PendingRequest $pendingRequest): ?PendingRequest
    {

        $requestClass = $pendingRequest->getRequest()::class;

        $this->getLogger()?->info("{$requestClass} Request:", [
            'connector' => $pendingRequest->getConnector()::class,
            'request' => $requestClass,
            'method'  => $pendingRequest->getMethod(),
            'uri'     => (string)$pendingRequest->getUri(),
            'headers' => $pendingRequest->headers(),
            'config' => $pendingRequest->config()->all(),
            'body'    => (string)$pendingRequest->body(),
        ]);

        return $pendingRequest;
    }

    // custom format response log
    protected function formatResponseLog(Response $response): ?Response
    {
        $requestClass = $response->getRequest()::class;

        $this->getLogger()?->info("{$requestClass} Response:", [
            'status'  => $response->status(),
            'headers' => $response->headers(),
            'body'    => $response->body(),
        ]);

        return $response;
    }
}

$connector = new ExampleConnector();
$connector->withLogger($logger);

```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contact

If you have any questions or suggestions, please contact:
- shadowmatthew1025@gmail.com