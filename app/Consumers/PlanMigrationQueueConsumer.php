<?php

namespace App\Consumers;

use Carbon\Carbon;

class PlanMigrationQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "PlanMigration";
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

        $modifiedData['pay_plan'] = $data[0]['pay_plan'];
        $modifiedData['plan_category_id'] = $data[0]['plan_category_id'];
        $modifiedData['sync_date'] = Carbon::now();
        $modifiedData['sync_medium'] = 'Consumer';

        // dd($modifiedData);
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
