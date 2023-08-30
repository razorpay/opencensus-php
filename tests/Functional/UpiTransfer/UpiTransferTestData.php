<?php

namespace RZP\Tests\Functional\UpiTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Gateway\Upi\Icici\Fields;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            "receivers" => [
                "types" => [
                    "vpa"
                ],
                "vpa"   => [
                    "descriptor" => "virtualvpa",
                ]
            ]
        ],
    ],

    'testFetchPaymentForBankReference' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 10000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'method'            => 'upi',
            ]
        ],
    ],

    'processUpiTransfer' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'processUpiTransferInternal' => [
        'url'     => '/callback/upi/upi_icici/internal',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpvpaicici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'processUpiTransferIgnoreCaseInternal' => [
        'url'     => '/callback/upi/upi_icici/internal',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'PayTo00000VirtualVpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessDuplicateIciciTransferWithAmountAndPayeeVpa' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '200.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'processUpiTransferIgnoreCase' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessFailedIciciUpiTransferPayment' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'FAILED',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'processIciciUpiTransferWithSmallPaymentAmount' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '1.50',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ]
    ],

    'testProcessIciciUpiTransferPayment' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciUpiTransferPaymentWithPayerAccountType' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'CREDIT|123456',
        ],
    ],

    'testProcessIciciUpiTransferWithPayerAccountTypeNonCredit' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'SAVINGS',
        ],
    ],

    'testProcessIciciUpiTransferPaymentWithInvalidPayerAccountType' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'invalidtype',
        ],
    ],

    'testProcessIciciUpiTransferPaymentWithTPVFeatureEnabled' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciUpiTransferPaymentWithTr' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcicixyz1234567',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessICICIUpiTransferToVaWithPastCloseBy' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000anothervpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ]
    ],

    'createVAWithAllowedPayer' => [
        'content' => [
            'receivers'      => [
                'types' => [
                    'vpa'
                ],
            ],
            'allowed_payers' => [
                [
                    'type'         => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0014823',
                        'account_number' => '765432123456789'
                    ]
                ],
            ],
        ],
    ],

    'adminFetchUpiTransferRequest' => [
        'request' => [
            'url'       => '/admin/upi_transfer_request/',
            'method'    => 'get',
            'content'   => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateVPAForPLAppWithOrder' => [
        'request' => [
            'url'     => '/virtual_accounts/internal',
            'method'  => 'post',
            'content' => [
                'amount_expected'   => 3500,
                'description'       => 'VA for tests',
                'order_id'          => 'order_100000000order',
                'receivers'   => [
                    'types' => [
                        'vpa',
                    ],
                    'vpa' => [
                        'descriptor' => '20digitsendfromapps0'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateVPAForPLAppWithOrderForIncorrectDescriptor' => [
        'request' => [
            'url'     => '/virtual_accounts/internal',
            'method'  => 'post',
            'content' => [
                'amount_expected'   => 3500,
                'description'       => 'VA for tests',
                'order_id'          => 'order_100000000order',
                'receivers'   => [
                    'types' => [
                        'vpa',
                    ],
                    'vpa' => [
                        'descriptor' => 'randomlessthan20'
                    ]
                ],
            ],
        ],
        'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Invalid length for descriptor.',
                    ],
                ],
                'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
        ],
    ],

    'testCreateVPAForPLAppWithOrderForNoDescriptor' => [
        'request' => [
            'url'     => '/virtual_accounts/internal',
            'method'  => 'post',
            'content' => [
                'amount_expected'   => 3500,
                'description'       => 'VA for tests',
                'order_id'          => 'order_100000000order',
                'receivers'   => [
                    'types' => [
                        'vpa',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'          => 'active',
                'amount_expected' => 3500,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVPAForPLAppWithPaidOrder' => [
        'request' => [
            'url'     => '/virtual_accounts/internal',
            'method'  => 'post',
            'content' => [
                'amount_expected'   => 3500,
                'description'       => 'VA for tests',
                'order_id'          => 'order_100000000order',
                'receivers'   => [
                    'types' => [
                        'vpa',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creation of new virtual accounts is currently blocked for this order.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER,
        ],
    ],

    'testCreateVPAForPLAppWithRandomOrder' => [
        'request' => [
            'url'     => '/virtual_accounts/internal',
            'method'  => 'post',
            'content' => [
                'amount_expected'   => 3500,
                'description'       => 'VA for tests',
                'order_id'          => 'order_100000000order',
                'receivers'   => [
                    'types' => [
                        'vpa',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreateVPAForPLAppWithOrderAndPay' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => '20digitsendfromapps0',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '35.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciUpiTransferForCustomerFeeBearer' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000vpVpaIcici',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '4000.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testUpiTransferRefundFailPaymentSuccess' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '1.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testWebhookUpiPaymentWithTr' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvparandomutr',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testUpiTransferWithLongVpaAddress' => [
        'url'     => '/test/upi/callback/icici/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'payto00000virtualvpa',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => '9931724380000000@paytm',
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

];
