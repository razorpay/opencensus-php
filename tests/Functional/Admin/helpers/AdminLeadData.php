<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateAdminLead' => [
        'request' => [
            'url' => '/orgs/%s/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'abc@xyz.com',
                'contact_name'  => 'test user',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],
];
