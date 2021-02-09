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
    'testJkbSuccessRecon' => [
        'BID'                => '1234',
        'PID'                => 'BANK_PID',
        'AMT'                => '500.00',
        'CRN'                => 'INR',
        'DATE'               => Carbon::today()->format("YmdHis"),
        'STATUS'             => 'S',
        'REAL'               => 'Y',
        'PRN'                => ''
    ],
    'testIdbiSuccessRecon' => [
        'Bank'                          => 'IDBI',
        'TRANSACTIONDATE'               => '',
        'PaymentGateway'                => 'RAZORPAY',
        'TransactionAmount'             => '500.00',
        'PaymentGatewayReferenceNumber' => '',
        'BankTransactionReferenceNo'    => '1234'
    ],
    'testIobSuccessRecon' => [
        'Bank Code'                             => 'IOB',
        'payment reference number'              => '',
        'Transaction Amount'                    => '500.00',
        'date and time DD/MM/YYYY HH24:mm:ss'   => Carbon::today()->format("d/m/Y H:i:s"),
        'Status of transaction'                 => 'Y',
        'bank ref no.'                          => '123456'
    ],
    'testUbiSuccessRecon' => [
        'Trasanction Date (YYYY-MM-DD)'         => Carbon::today()->format("Y-m-d"),
        'PRN                     '              => '123456',
        'RazorPay(Hardcoded Value)'             => '',
        'Account Number'                        => '123456789',
        'Amount'                                => '500.00',
    ],
    'testCbiSuccessRecon' => [
        'Bank Code'                             => 'CBIN',
        'payment reference number'              => '',
        'Transaction Amount'                    => '500.00',
        'date'                                  => Carbon::today()->format("Ymd"),
        'Status of transaction'                 => 'Y',
        'bank ref no.'                          => '123456'
    ],
    'testIbkSuccessRecon' => [
        'pid'             => 'PAYGATE16',
        'Biller Name'     => 'Razorpay',
        'Date & Time'     => '',
        'Merchant Ref No' => '',
        'Amount'          => '500',
        'Currency'        => 'INR',
        'Customer_no'     => '3189671675',
        'Date_bank'       => '2020-12-29 15:39:54.0',
        'bank_ref_no'     => '1234',
        'Journal_no'      => '012371800',
        'Paidstatus'      => 'Y',
    ],

    'testScbSuccessRecon' => [
        'Bank Code'                             => 'SCBL',
        'payment reference number'              => '',
        'Transaction Amount'                    => '500.00',
        'date'   => Carbon::today()->format("d/m/Y"),
        'Status of transaction'                 => 'Y',
        'bank ref no.'                          => '123456'
    ],

    'testAusfSuccessRecon' => [
        'TRANSACTION TYPE'              => 'PAYMENT',
        'CHANNEL_REF_NO'                => 'PG-20210205195519793000000',
        'PAYMENT_ID_EXT'                => '',
        'MERCHANT_ID'                   => 'RAZORPAY',
        'USERREFERENCENO'               => '\'GXvNgN82wtLvkD',
        'HOST_REF_NO'                   => 'CBSFund1612535152270',
        'EXTERNALREFERENCEID_EXT'       => '',
        'PAYMENT_DATE'                  => '05-FEB-21 07.54.46.387000000 PM',
        'PAYMENT_AMT'                   => '2',
        'REFUND_AMOUNT'                 => '',
        'DEBIT_ACCOUNT_NO'              => '\'1712220914442391',
        'STATUS'                        => 'S',
        'MERCHANT_ACCT_NO'              => '\'2121201131751367',
        'MERCHANT_URL'                  => 'https://www.razorpay.com',
    ],
];
