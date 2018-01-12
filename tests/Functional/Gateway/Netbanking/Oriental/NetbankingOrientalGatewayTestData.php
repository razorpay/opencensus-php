<?php
/**
 * Created by PhpStorm.
 * User: mayankamencherla
 * Date: 12/01/18
 * Time: 4:26 PM
 */

namespace RZP\Tests\Functional\Gateway\Oriental;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;

return [
    'testPayment' => [
        'entity'          => 'netbanking',
        'amount'          => 500,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'testPaymentFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'netbankingPaymentFailed' => [
        'entity'          => 'netbanking',
        'amount'          => 500,
        'status'          => 'N',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ]
];
