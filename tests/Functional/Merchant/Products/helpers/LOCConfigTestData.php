<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateLOCProductConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'line_of_credit'
            ],
        ],
        'response'  => [
            'content' => [
                "requested_configuration" => [],
                "active_configuration" => [],
                "requirements" => [],
                "tnc" => [],
                "product_name" => "line_of_credit",
            ],
        ],
    ],
    'createAccountV2ForMandatoryFilledByCapitalPartner' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'phone'               => '9999999999',
                'contact_name'        => 'contactname',
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme Corp Pvt Ltd',
                'contact_name'        => 'contactname',
            ],
        ],
    ],
];
