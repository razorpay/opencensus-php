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

    'processUpiTransfer' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000virtualvpa@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'processUpiTransferIgnoreCase' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|RZPY.TEST000000VIRTUALVPA@HDFCBANK!NA!NA|NA|NA'
        ],
    ],

    'processMindgateUpiTransferWithSmallPaymentAmount' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365268|paysucc123|1.50|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000001virtualvpa@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'testProcessFailedMindgateUpiTransferPayment' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|FAILED|Transaction fail:Debit Failed|U30|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000virtualvpa@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'testProcessMindgateUpiTransferUnexpectedPayment' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@hdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000vpatest1@hdfcbank!NA!NA|NA|NA'
        ],
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

    'testProcessMindgateUpiTransferToVaWithPastCloseBy' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000anothervpa@hdfcbank!NA!NA|NA|NA'
        ],
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

    'testUpiTransferValidateTpvWithValidPayerDetails' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!765432123456789!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000testvpatpv@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'testUpiTransferValidateTpvWitInvalidPayerDetails' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000testvpatpv@hdfcbank!NA!NA|NA|NA'
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
];
