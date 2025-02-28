<?php

namespace Weijiajia\SaloonphpLogsPlugin;

use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Enums\PipeOrder;

trait HasLogger
{
    protected bool $isLog = false;

    protected ?LoggerInterface $logger = null;

    public function withLogger(?LoggerInterface $logger = null): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function bootHasLogger(PendingRequest $pendingRequest): void
    {
        if ($this->isLog) {
            return;
        }

        $this->isLog = true;
        
        $pendingRequest->getConnector()
                ->middleware()
                ->onRequest(
                    fn(PendingRequest $request) => $this->formatRequestLog($request),
                    'logger_request',
                    PipeOrder::LAST
                );

        $pendingRequest->getConnector()
                ->middleware()
                ->onResponse(
                    fn(Response $response) => $this->formatResponseLog($response),
                    'logger_response',
                    PipeOrder::FIRST
                );
    }

    protected function formatRequestLog(PendingRequest $pendingRequest): ?PendingRequest
    {

        $requestClass = $pendingRequest->getRequest()::class;


        $headers = array_map(function ($value) {
            return implode(';', $value);
        }, $pendingRequest->createPsrRequest()->getHeaders());

        $this->getLogger()?->info("{$requestClass} Request:", [
            'connector' => $pendingRequest->getConnector()::class,
            'request' => $requestClass,
            'method'  => $pendingRequest->getMethod(),
            'uri'     => (string)$pendingRequest->getUri(),
            'headers' => $headers,
            'config' => $pendingRequest->config()->all(),
            'body'    => (string)$pendingRequest->body(),
        ]);

        return $pendingRequest;
    }

    protected function formatResponseLog(Response $response): ?Response
    {
        $requestClass = $response->getRequest()::class;

        $headers = array_map(function ($value) {
            return implode(';', $value);
        }, $response->getPsrResponse()->getHeaders());

        $this->getLogger()?->info("{$requestClass} Response:", [
            'status'  => $response->status(),
            'headers' => $headers,
            'body'    => $response->body(),
        ]);

        return $response;
    }
}