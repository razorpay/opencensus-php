<?php

namespace RZP\Tests\Functional\UpiTransfer;

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

    'testProcessFailedUpiTransferPayment' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|FAILED|Transaction fail:Debit Failed|U30|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000virtualvpa@hdfcbank!NA!NA|NA|NA'
        ],
    ],

    'testProcessUpiTransferUnexpectedPayment' => [
        'url'     => '/test/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@hdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzpy.test000000vpatest1@hdfcbank!NA!NA|NA|NA'
        ],
    ],
];
