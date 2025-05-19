<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\User\Entity as UserEntity;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testFetchMerchantsOfAUserForLoginWithPG'=>[
        'request' => [
            'url'     => '/users/id/merchants',
            'method'  => 'GET',
            'content' => [
                'intent'                => 'auth'
            ],
            'headers'    => [
                'X-Org-Hostname'    => 'dashboard.razorpay.in',
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ]
];
