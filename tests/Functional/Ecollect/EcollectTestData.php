<?php

namespace RZP\Tests\Functional\Ecollect;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testEcollectValidate' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
                'message' => null,
            ],
        ],
    ],

    'testEcollectValidateRazorp' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RAZORP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
                'message' => null,
            ],
        ],
    ],

    'testEcollectValidateFalse' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'ABC1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => false,
                'message' => 'Invalid account number',
            ],
        ],
    ],

    'testEcollectValidateFailure' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payee_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payer ifsc field is required.',
                    'field'       => 'payer_ifsc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEcollectPay' => [
        'request' => [
            'url' => '/ecollect/pay',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testEcollectPayFailure' => [
        'request' => [
            'url' => '/ecollect/pay',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payee ifsc field is required.',
                    'field'       => 'payee_ifsc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
