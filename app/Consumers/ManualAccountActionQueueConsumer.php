<?php

namespace App\Consumers;

class ManualAccountActionQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "ManualAccountAction";
    }

    protected function transformPayload($data)
    {
        $data = $data['response'][0];
        $data['client_name'] = $data['username'] . rand();
        return $data;
    }

    protected function getHttpMethod(): string
    {
        return "PATCH";
    }

    protected function sourceApiQueryParams(): string
    {
        return "";
    }

    protected function destinationApiQueryParams(): string
    {
        return "?event=customerInfo";
    }
}
