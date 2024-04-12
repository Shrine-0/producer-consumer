<?php

return [
    'queueExchange' => [
        'customersvc.prod.create.customer' => 'customersvc.prod.insert.mobileApp',
        'customersvc.prod.update.customerinfo' => 'customersvc.prod.update.customerinfo.mobileApp',
        'customersvc.prod.planshift' => 'customersvc.prod.planshift.mobileApp',
        'customersvc.prod.expirydate_update.customer' => 'customersvc.prod.expirydate_update.mobileApp'
    ],
    'consumerCommandName' => [
        'customersvc.prod.insert.mobileApp' => 'OnUserCreate',
        'customersvc.prod.update.customerinfo.mobileApp' => 'CustomerInfoModification',
        'customersvc.prod.planshift.mobileApp' => 'PlanMigration',
        'customersvc.prod.expirydate_update.mobileApp' => 'AccountPayment'
    ]
];
