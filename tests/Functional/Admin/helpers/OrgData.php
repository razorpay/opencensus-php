<?php

return [

    'testCreateOrg' => [
        'request' => [
            'url' => '/org',
            'method' => 'post',
            'content' => [
                'email_domains' => 'hdfc.com,fbapi.com',
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => 'hdfc.com,fbapi.com',
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],
];
