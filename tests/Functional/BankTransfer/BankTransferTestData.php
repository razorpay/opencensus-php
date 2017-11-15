<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [],
    ],

    'processBankTransfer' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [],
    ],

    'notifyBankTransfer' => [
        'url'     => '/ecollect/pay',
        'method'  => 'post',
        'content' => [],
    ],

    'processOrNotifyBankTransfer' => [
        'url'     => null,
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferImps' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDB9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferInsert' => [
        'url'     => '/bank_transfers/dashboard',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees, inserted',
        ],
    ],

    'testBankTransferFloatingPointImprecision' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 579.3,
            'description'    => 'NEFT payment of 579 rupees and 30 paise',
        ],
    ],

    'testBankTransferSpecialCharsInAccNumber' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '123-123-123',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid account number',
        ],
    ],

    'testBankTransferStripPayerBankAccount' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '00000000000123456',
            'payer_ifsc'     => 'ABC9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with leading zeroes',
        ],
    ],

    'testBankTransferImpsUnmappedBankCode' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferRefundRetry' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferImpsFromRogueBankNullAccount' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '',
            'payer_ifsc'     => '',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with no account number',
        ],
    ],

    'testBankTransferImpsFromRogueBankInvalidAccount' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => '533/1 NEFT CASH FOR NON CUSTOMER',
            'payer_account'  => '533/1 NEFT CASH FOR NON CUSTOMER',
            'payer_ifsc'     => 'PJSB0000003',
            'mode'           => 'rtgs',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with nonsense account number',
        ],
    ],

    'testBankTransferImpsFromRogueBankStripAccount' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '00000000000123456',
            'payer_ifsc'     => 'CNB9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with leading zeroes',
        ],
    ],

    'bankTransferImpsFailedRefund' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Refund is currently not supported for this payment method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
        ],
    ],

    'testBankTransferProcessFailure' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_4567',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid' => false,
            ],
        ],
    ],

    'testBankTransferNotifyFailure' => [
        'request' => [
            'url' => '/ecollect/pay',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 148415544000,
                'amount'         => 50000,
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

    'testBankTransferPublicAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method given: bank_transfer',
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
