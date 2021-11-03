<?php

use Carbon\Carbon;

return [
    'testLazypaySuccessRecon' => [
        'Merchant ID'                       => 'Merchant Id',
        'Merchant name'                     => 'Merchant name',
        'Payment mode'                      => 'lazypay',
        'Transaction Type'                  => 'Sale',
        'Transaction date'                  => Carbon::today()->format("Ymd"),
        'Transaction amount'                => '500.00',
        'Transaction currency'              => 'INR',
        'Merchant reference number'         => 'rzp_2231',
        'Citrus reference number'           => 'adf',
        'PG reference  ID'                  => '',
        'Payment confirmation ID'           => '',
        'Issuer Txn ref number'             => 'gateway_payemnt_id',
        'CPGMID'                            => '1',
        'Org PG reference ID'               => '1',
        'Org payment confirmation ID'       => '1',
        'Org issuer txn reference number'   => '110',
        'Net banking payment ID'            => '',
        'PG payment ID'                     => '',
        'pg_txn_no'                         => '',
    ],
];
