<?php

namespace App\Consumers;

class PlanMigrationQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "PlanMigration";
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
