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
        return [
            [
                "api" => "https://services.wlink.com.np/customers/customers/$username",
                "headers" => [
                    "headers" => [
                        "Authorization" => "Basic aW50X21vYmlsZWFwcDpWV0paZXBXbWNxM2pha0hr"
                    ]
                ],
                "method" => "get"
            ]
        ];
    }

    protected function transformPayload($data): array
    {
        $modifiedData = [];
        $modifiedData['account_status'] = ($data[0]['disable'] == 'N') ? 'enable' : 'disable';
        $modifiedData['expiry_date'] = $data[0]['expiry_date'];
        // $modifiedData['pay_plan'] = $data[0]['pay_plan'];
        // $modifiedData['plan_category_id'] = $data[0]['plan_category_id'];

        return $modifiedData;
    }

    protected function getDestinationApiHttpMethod(): string
    {
        return "patch";
    }

    protected function destinationApiQueryParams($username): string
    {
        return "$username?event=customerInfo";
    }
}
