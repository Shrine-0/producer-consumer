<?php

return [
    'AccountPayment' => [
        'destination_api' => env('BASE_SERVICE_URL').'/v1/base/update/',
    ],
    'CustomerInfoModification' => [
        'destination_api' => env('BASE_SERVICE_URL').'/v1/base/update/',
    ],
    'OnUserCreate' => [
        'destination_api' => env('BASE_SERVICE_URL').'/v1/base/insert/',
    ],
    'PlanMigration' => [
        'destination_api' => env('BASE_SERVICE_URL').'/v1/base/insert/',
    ]
];