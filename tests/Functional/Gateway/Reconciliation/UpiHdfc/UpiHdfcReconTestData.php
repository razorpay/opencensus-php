<?php

return [
    'upiHdfc' => [
        'External Mid'         => '1234',
        'External TID'         => '2345',
        'Upi Merchant Id'      => 'UPI Merchant ID',
        'Merchant Name'        => 'Airtel',
        'Merchant VPA'         => 'airtel@hdfcbank',
        'Payer VPA'            => 'paytessy@hdfc',
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
        'CR / DR'              =>  'CR',
    ],
    'upiHdfcRefund' => [
        'PG Merchant ID'       => '1234',
        'External TID'         => '2345',
        'Order No'             => 'pay_1234',
        'Trans Ref No.'        => '1234512345',
        'Customer Ref No.'     => '123412341234',
        'DR/CR'                => 'Debit',
        'Transaction Status'   => 'SUCCESS',
        'Transaction Remarks'  => 'Refund for Zomato Online Order',
        'Transaction Date'     => '13-Mar-2019 23:10:34',
        'Transaction Amount'   => 500,
        'Payee Virtual Address'=> 'vsk@hdfc',
        'Settlement Status'    => 'Reconciled',
        'New Refund Order ID'  => 'ABCD1234ABCD12',
    ],

    'bulk_reconcile_via_batch_service' => [
        'request' => [
            'url'     => '/reconciliate/batch_service/bulk',
            'method'  => 'post',
            'server' => [
                'HTTP_X_Batch_Id' => "C0zv9I46W4wiOq",
                'mode'            =>  'live',
                'X-Entity-Id'     =>  'MID'
            ],
        ],
        'response' => [
            'content' => [],
        ]
    ],
];
