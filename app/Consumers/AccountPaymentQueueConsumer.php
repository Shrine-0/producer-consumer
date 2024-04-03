<?php

namespace App\Consumers;

class AccountPaymentQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "AccountPayment";
    }

    protected function sourceApiConfig($username): array
    {
        return [];
    }

    protected function transformPayload($data): array
    {
        return [];
    }

    protected function getDestinationApiHttpMethod(): string
    {
        return "";
    }

    protected function destinationApiQueryParams(): string
    {
        return "";
    }
}
