<?php

namespace App\Consumers;

use App\Helpers\Encrypter;
use Carbon\Carbon;

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
        $modifiedData = [];

        $modifiedData['username'] = $data[0]['user_name'] . rand();
        $modifiedData['client_name'] = $data[1]['name'];
        $modifiedData['email_primary'] = $data[1]['primary_email_address'];
        $modifiedData['email_secondary'] = $data[1]['secondary_email_address'];
        $modifiedData['account_status'] = ($data[0]['disable'] == 'N') ? 'enable' : 'disable';
        $modifiedData['primary_number'] = Encrypter::handle($data[1]['primary_mobile_number']);
        $modifiedData['secondary_number'] = Encrypter::handle($data[1]['secondary_mobile_number']);
        $modifiedData['account_type'] = $data[1]['account_type'];
        $modifiedData['pay_plan'] = $data[1]['pay_plan'];
        $modifiedData['plan_category_id'] = $data[0]['plan_category_id'];
        $modifiedData['supportzone_id'] = $data[0]['supportzone_id'];
        $modifiedData['supportzone'] = $data[0]['support_zone'];
        $modifiedData['sync_date'] = Carbon::now();
        $modifiedData['sync_medium'] = 'Consumer';
        $modifiedData['ownership'] = $this->getOwnership($data[1]['pay_plan']);
        $modifiedData['member_start_date'] = $data[0]['create_date'];
        $modifiedData['expiry_date'] = $data[0]['expiry_date'];

        return $modifiedData;
    }

    protected function getDestinationApiHttpMethod(): string
    {
        return "post";
    }

    protected function destinationApiQueryParams($username): string
    {
        return "";
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
