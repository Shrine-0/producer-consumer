<?php

return [
    'AccountPayment' => [
        'destination_api' => env('BASE_SERVICE_URL') . '/v1/base/update/',
    ],
    'CustomerInfoModification' => [
        'destination_api' => env('BASE_SERVICE_URL') . '/v1/base/update/',
    ],
    'OnUserCreate' => [
        'destination_api' => env('BASE_SERVICE_URL') . '/v1/base/insertOrUpdate/',
    ],
    'PlanMigration' => [
        'destination_api' => env('BASE_SERVICE_URL') . '/v1/base/update/',
    ],
    'slackService' => [
        'base_uri' => 'https://hooks.slack.com/services/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',
    ]
];
