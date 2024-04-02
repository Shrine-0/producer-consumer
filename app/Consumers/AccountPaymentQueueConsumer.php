<?php

namespace App\Consumers;

class AccountPaymentQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "AccountPayment";
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
