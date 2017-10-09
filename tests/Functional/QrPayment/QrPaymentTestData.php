<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'bharat_qr'
        ],
    ],

    'testQrPaymentProcess' => [
        'url'     => '/qr/payment/process',
        'method'  => 'post',
        'content' => [
            'F002'       => '423156XXXXXX1234',
            'F003'       => '26000',
            'F004'       => '1.00',
            'F011'       => 'abc123',
            'F012'       => '120000',
            'F013'       => '1212',
            'F037'       => 'somethingrandom',
            'F038'       => 'randomauthorization',
            'F039'       => '0',
            'F041'       => 'abc',
            'F042'       => 'random',
            'F043'       => 'RazorpayBangalore',
            'F102'       => 'paymentId',
            'PurchaseID' => 'tobefilled',
            'SenderName' => 'Razorpay',
        ],
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

    'testBankTransferImpsUpmappedBankCode' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ987654321',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferImpsFromRogueBank' => [
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
                'payee_ifsc'     => 'IFSC0009876',
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
                'valid'          => false,
                'message'        => null,
            ],
            'status_code' => 200,
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
