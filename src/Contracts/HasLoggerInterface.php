<?php

namespace Weijiajia\SaloonphpLogsPlugin\Contracts;

use Psr\Log\LoggerInterface;
use GuzzleHttp\MessageFormatter;

interface HasLoggerInterface
{
    public function withLogger(?LoggerInterface $logger = null): static;

    public function getLogger(): ?LoggerInterface;

    public function withMessageFormatter(MessageFormatter $messageFormatter): static;

    public function getMessageFormatter(): MessageFormatter;
}