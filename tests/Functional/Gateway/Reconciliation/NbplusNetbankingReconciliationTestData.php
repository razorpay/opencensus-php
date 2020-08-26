<?php

use Carbon\Carbon;

return [
    'testSvcSuccessRecon' => [
        'MID'                => 'RAZPGALL',
        'CRN'                => '',
        'TIC'                => '1234',
        'TRANSACTION_AMOUNT' => '500.00',
        'Status'             => 'Y',
        'TRANSACTION_DATE'   => Carbon::today()->format("Ymd")
    ],
    'testIciciSuccessRecon' => [
        'ITC'                => '',
        'PRN'                => '',
        'BID'                => '1234',
        'amount'             => '500.00',
        'Date'               => Carbon::today()->format("Y-d-m")
    ],
    'testFsbSuccessRecon' => [
        'AggregatorReferenceNumber'     => '',
        'BankTransactionReferenceNo'    => '1234',
        'TransactionAmount'             => '500.00',
        'STATUS'                        => 'Y',
        'TRANSACTIONDATE'               => Carbon::today()->format("Y-m-d"),
        'Account_Number'                => '123456789'
    ],
];
