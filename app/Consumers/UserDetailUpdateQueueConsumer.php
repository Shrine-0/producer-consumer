<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Config;

class UserDetailUpdateQueueConsumer extends QueueConsumer
{
    protected function getQueueName(): string
    {
        return 'UserDetailUpdateQueue';
    }

    protected function transformPayload($data)
    {
        // Perform transformation on the data
        $data = $data['response'][0];
        // sample transform here
        $data['client_name'] = "Samir Husen Don";
        return $data;
    }

    protected function getHttpMethod(): string
    {
        return 'PATCH';
    }
}
