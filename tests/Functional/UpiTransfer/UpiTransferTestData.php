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
                    "descriptor" => "testvpa",
                ]
            ]
        ],
    ],

    'processUpiTransfer' => [
        'url'     => '/live/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@yesbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzp.test.testvpa@hdfcbank!NA!NA|NA|NA'

        ],
    ],

    'testProcessUpiTransferUnexpectedPayment' => [
        'url'     => '/live/upi/callback/hdfc/upi_mindgate',
        'method'  => 'post',
        'content' => [
            'pgMerchantId' => 'HDFCVPATEST',
            'meRes'        => '1861365267|paysucc123|100.00|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@yesbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|rzp.test.vpatest@hdfcbank!NA!NA|NA|NA'

        ],
    ],
];
