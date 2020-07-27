<?php

namespace RZP\Tests\Functional\UpiTransfer;

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
            Fields::PAYER_AMOUNT        => '100.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessMindgateUpiTransferToDueToBeClosedVa' => [
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

    'testUpiTransferValidateTpvWithValidPayeeDetails' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!765432123456789!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000testvpatpv@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'testUpiTransferValidateTpvWitInvalidPayeeDetails' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000testvpatpv@hdfcbank!NA!NA|NA|NA'
        ],
    ],
];
