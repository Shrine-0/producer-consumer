<?php

namespace App\Consumers;

class CustomerInfoModificationQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "CustomerInfoModification";
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
