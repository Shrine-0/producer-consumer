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
        $remainingDaysInDate = Carbon::now();
        $remainingDaysInDate->addDays((int) $data[1]['days_remaining']);
        $remainingDaysInDate->setHour(00)->setMinute(00)->setSecond(00);
        $modifiedData = [];

        $modifiedData['pay_plan'] = $data[0]['pay_plan'];
        $modifiedData['plan_category_id'] = $data[0]['plan_category_id'];
        $modifiedData['sync_date'] = Carbon::now();
        $modifiedData['sync_medium'] = 'PlanMigrationConsumer';
        $modifiedData['valid_upto'] = $remainingDaysInDate;

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
