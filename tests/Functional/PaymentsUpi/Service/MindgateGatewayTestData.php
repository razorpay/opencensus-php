<?php

return [
    'testUnexpectedPaymentSuccess' => [
        'pgMerchantId' => 'razorpay upi mindgate',
        'meRes' => '1861365267|paysucc123|2279.24|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|NA|NA'
    ],
    'upiHdfc' => [
        'External Mid'         => '1234',
        'External TID'         => '2345',
        'Upi Merchant Id'      => 'razorpay upi mindgate',
        'Merchant Name'        => 'Airtel',
        'Merchant VPA'         => 'airtel@okhdfcbank',
        'Payer VPA'            => 'paytessy@okhdfcbank',
        'UPI Trxn ID'          => 'HDF608049A22F434EA7ADD1B2E7024C349D',
        'Order ID'             => 'Amex5juTJFVaHl',
        'Txn ref no. (RRN)'    => '822923226018',
        'Transaction Req Date' => '17-aug-2018 23:46:52',
        'Settlement Date'      => '18-aug-2018 00:24:37',
        'Transaction Amount'   => '500',
        'MSF Amount'           => '0',
        'Net Amount'           => '0',
        'Trans Type'           => 'COLLECT',
        'Pay Type'             => 'P2M',
        'CR / DR'              => 'CR',
    ]
];
