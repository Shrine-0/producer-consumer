<?php

namespace App\Consumers;

class OnUserCreateQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "OnUserCreate";
    }

    protected function sourceApiConfig($username): array
    {
        $data = [
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
                "api" => "https://services.wlink.com.np/customers/customerinfos/$username",
                "headers" => [
                    "headers" => [
                        "Authorization" => "Basic aW50X21vYmlsZWFwcDpWV0paZXBXbWNxM2pha0hr"
                    ]
                ],
                "method" => "get"
            ]
        ];
        return $data;
    }

    protected function transformPayload($data): array
    {
        // transformation with foreach
        return $data;
    }

    protected function getDestinationApiHttpMethod(): string
    {
        return "PATCH";
    }

    protected function destinationApiQueryParams(): string
    {
        return "?event=customerInfo";
    }

    protected function getHeader(): array
    {
        $type = 'Basic';
        return ['Authorization' => $type . " aW50X21vYmlsZWFwcDpWV0paZXBXbWNxM2pha0hr"];
    }
}
