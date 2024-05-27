<?php

namespace App\Consumers;

use Carbon\Carbon;

class NetTvQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "NetTv";
    }

    protected function sourceApiConfig($username): array
    {
        return [
            [
                "api" => "https://services.wlink.com.np/customers/customers/$username",
                "headers" => [
                    "headers" => [
                        "Authorization" => "Basic aW50X21vYmlsZWFwcDpWV0paZXBXbWNxM2pha0hr"
                    ]
                ],
                "method" => "get"
            ],
            [
                "api" => "https://services.wlink.com.np/customers/customers/$username/status",
                "headers" => [
                    "headers" => [
                        "Authorization" => "Basic aW50X21vYmlsZWFwcDpWV0paZXBXbWNxM2pha0hr"

                    ]
                ]
            ]
        ];
    }

    protected function transformPayload($data): array
    {
        $modifiedData = [];

        $modifiedData['account_status'] = ($data[0]['disable'] == 'N') ? 'enable' : 'disable';
        $modifiedData['expiry_date'] = $data[0]['expiry_date'];
        $modifiedData['sync_medium'] = 'NetTvConsumer';
        $modifiedData['sync_date'] = Carbon::now();

        return $modifiedData;
    }

    protected function getDestinationApiHttpMethod(): string
    {
        return "patch";
    }

    protected function destinationApiQueryParams($username): string
    {
        return "$username";
    }

    protected function getQueryParams(): array
    {
        return [
            "event" => "NetTv",
            "username" => $this->username
        ];
    }
}
