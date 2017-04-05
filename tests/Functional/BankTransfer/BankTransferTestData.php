<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testBankTransferValidate' => [
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
                'valid'          => true,
                'message'        => null,
                'transaction_id' => 'vba_1234',
            ],
        ],
    ],

    'testBankTransferValidateRazorp' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RAZORP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_2345',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'          => true,
                'message'        => null,
                'transaction_id' => 'vba_2345',
            ],
        ],
    ],

    'testBankTransferValidateDuplicateUtr' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RAZORP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_duplicate',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'          => false,
                'message'        => 'Duplicate UTR received',
                'transaction_id' => 'vba_duplicate',
            ],
        ],
    ],

    'testBankTransferValidateFalse' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'ABC1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_3456',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'          => false,
                'message'        => 'Invalid account number',
                'transaction_id' => 'vba_3456',
            ],
        ],
    ],

    'testBankTransferValidateFailure' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payee_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_4567',
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

    'testBankTransferPay' => [
        'request' => [
            'url' => '/ecollect/pay',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payee_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'IFSC0001234',
                'mode'           => 'neft',
                'transaction_id' => 'vba_5678',
                'time'           => 1484155440,
                'amount'         => 5000000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'success'        => true,
                'message'        => null,
                'transaction_id' => 'vba_5678',
            ],
        ],
    ],

    'testBankTransferPayFailure' => [
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
