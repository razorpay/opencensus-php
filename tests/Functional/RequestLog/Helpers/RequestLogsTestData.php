<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePayoutWithIncorrectRequestBody' => [
        'request'   => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' =>
                [
                    'fund_account_id' => 'fa_fa100000000xyz',
                    'amount'          => 100,
                    'mode'            => 'UPI',
                    'currency'        => 'INR',
                    'account_number'  => '2224440041626905',
                    'purpose'         => 'refund'
                ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
];
