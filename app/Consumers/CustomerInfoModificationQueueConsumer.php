<?php

namespace App\Consumers;

use App\Helpers\Encrypter;
use Carbon\Carbon;

class CustomerInfoModificationQueueConsumer extends QueueConsumer
{
    protected function getEventName(): string
    {
        return "CustomerInfoModification";
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
                "api" => "https://services.wlink.com.np/customers/customerinfos/$username",
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
        $remainingDaysInDate->addDays((int) $data[2]['days_remaining']);
        $remainingDaysInDate->setHour(00)->setMinute(00)->setSecond(00);
        $modifiedData = [];

        $modifiedData['client_name'] = $data[1]['name'];
        $modifiedData['email_primary'] = $data[1]['primary_email_address'];
        $modifiedData['email_secondary'] = $data[1]['secondary_email_address'];
        $modifiedData['primary_number'] = Encrypter::handle($data[1]['primary_mobile_number']);
        $modifiedData['secondary_number'] = Encrypter::handle($data[1]['secondary_mobile_number']);
        $modifiedData['supportzone_id'] = $data[0]['supportzone_id'];
        $modifiedData['supportzone'] = $data[0]['support_zone'];
        $modifiedData['sync_date'] = Carbon::now();
        $modifiedData['sync_medium'] = 'CustomerInfoModificationConsumer';
        $modifiedData['valid_upto'] = $remainingDaysInDate;

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
            "event" => "BaseInfoUpdate",
            "username" => $this->username
        ];
    }

    private function getOwnership($payPlan)
    {
        switch ($payPlan) {
            case '8000':
                return 'WIFINEPAL';
            case '8001':
                return 'EASTLINK';
            default:
                return 'WORLDLINK';
        }
    }
}
