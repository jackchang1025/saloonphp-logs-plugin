<?php

namespace Weijiajia\SaloonphpLogsPlugin;

use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Senders\GuzzleSender;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Weijiajia\SaloonphpLogsPlugin\Contracts\HasLoggerInterface;
trait HasLogger
{
    protected ?LoggerInterface $logger = null;

    protected ?MessageFormatter $messageFormatter = null;

    protected bool $isLoaded = false;

    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    public function setLoaded(bool $loaded): void
    {
        $this->isLoaded = $loaded;
    }

    public function withLogger(?LoggerInterface $logger = null): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getMessageFormatter(): MessageFormatter
    {
        return $this->messageFormatter ??= new MessageFormatter(
            <<<FORMAT
{method} {uri} HTTP/{version}
{req_headers}

{req_body}
----------
HTTP/{version} {code} {phrase}
{res_headers}

{res_body}
FORMAT
        );
    }

    public function withMessageFormatter(MessageFormatter $messageFormatter): static
    {
        $this->messageFormatter = $messageFormatter;

        return $this;
    }

    public function bootHasLogger(PendingRequest $pendingRequest): void
    {
        $connector = $pendingRequest->getConnector();
        $request = $pendingRequest->getRequest();
        
        $sender = $connector->sender();
        if(!$sender instanceof GuzzleSender) {
            throw new HasLoggerException('The sender must be an instance of GuzzleSender to use the HasLogger plugin');
        }

        if (! $request instanceof HasLoggerInterface && ! $connector instanceof HasLoggerInterface) {
            throw new HasLoggerException(sprintf('Your connector or request must implement %s to use the HasLogger plugin', HasLoggerInterface::class));
        }

        /** @var HasLoggerInterface $loggerManager */
        $loggerManager = $request instanceof HasLoggerInterface
        ? $request
        : $connector;

        $logger = $loggerManager->getLogger();
        $messageFormatter = $this->getMessageFormatter();

        if($loggerManager->isLoaded() || !$logger) {
            return;
        }

        $loggerManager->setLoaded(true);

        /** @var GuzzleSender $sender */
       $sender->addMiddleware(function (callable $handler) use ($logger, $messageFormatter) {
            return function (RequestInterface $request, array $options) use ($handler, $logger, $messageFormatter) {
                return $handler($request, $options)->then(
                    function ($response) use ($logger, $request, $messageFormatter, $options) {

                        $logger->debug($messageFormatter->format($request, $response));
                        return $response;
                    },
                    function ($reason) use ($logger, $request, $messageFormatter, $options) {
                        $response = $reason instanceof RequestException
                            ? $reason->getResponse()
                            : null;

                        $logger->error($messageFormatter->format($request, $response, $reason));
                        return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                    }
                );
            };
        },'saloon.logger');
    }

}