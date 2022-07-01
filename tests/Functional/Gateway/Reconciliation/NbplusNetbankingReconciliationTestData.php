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
    'testIciciRefundRecon' => [
        'Payee id'         => '',
        'Payee Name'       => '',
        'Payment id'       => '',
        'ITC'              => '',
        'PRN'              => '',
        'Txn Amount'       => '1000.00',
        'Reversal Amount'  => '500.00',
        'Reversal Date'    => Carbon::today()->format("Ymd"),
        'ReversalId'       => '1234',
        'Status'           => 'W',
        'Reason'           => '',
        'SPID'             => '1234',
        'Sub-merchant Name'=> '',
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
        'PRN'                                   => '123456',
        'Amount'                                => '500.00',
        'Trasanction Date (MM/DD/YY)'           => Carbon::today()->format("m/d/y"),
        'RazorPay(Hardcoded Value)'             => '',
        'Payment Id'                            => '',
        'Account Number'                        => '123456789',
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

    'testAusfCorpSuccessRecon' => [
        'TRANSACTION TYPE'              => 'PAYMENT',
        'CHANNEL_REF_NO'                => 'PG-20210205195519793000000',
        'PAYMENT_ID_EXT'                => 'JHvB2k2UByIFkn',
        'MERCHANT_ID'                   => 'RAZORPAYPGCNB',
        'USERREFERENCENO'               => '31080422006',
        'HOST_REF_NO'                   => 'CBSFund1612535152270',
        'EXTERNALREFERENCEID_EXT'       => 'JHvB2k2UByIFkn',
        'PAYMENT_DATE'                  => '16-02-22 1:57:42.795000 AM',
        'PAYMENT_AMT'                   => '55',
        'REFUND_AMOUNT'                 => '0',
        'DEBIT_ACCOUNT_NO'              => '1721220614979099',
        'STATUS'                        => 'S',
        'MERCHANT_ACCT_NO'              => '2121201131751367',
        'MERCHANT_URL'                  => 'https://www.razorpay.com',
    ],

    'testKotakV2SuccessRecon' => [
        'Entity Code'               => '123456',
        'Merchant Code'             => '123456',
        'MCC Code'                  => '6211',
        'Party Name'                => 'RAZORPAY',
        'Party CRN'                 => 'INR',
        'FROM APAC'                 => '123456',
        'Transaction Amount'        => '500.00',
        'Charges'                   => '0',
        'GST'                       => '0',
        'Net Settlement Amount'     => '500.00',
        'Request Date'              => Carbon::today()->format("dmY"),
        'Entity Reference No'       => '',
        'Bank Reference No'         => '1234',
        'Payment Date'              => Carbon::today()->format("dmY"),
        'fc_process_date'           => Carbon::today()->format("dmY")
    ],

    'testKotakSuccessRecon' => [
        'Merchant ID'                   => '123456',
        'Merchant ID 2'                 => '123456',
        'Contact No'                    => '6211',
        'Customer Name'                 => 'RAZORPAY',
        'Bank ID'                       => '12345678',
        'Bank ID 2'                     => '12345678',
        'Amount'                        => '500.00',
        'Date'                          => Carbon::today()->format("d-m-Y"),
        'Int Payment ID'                => '0',
        'Processed'                     => 'C',
        'Combined Details'              => '123456 0 1234567',
        'Bank Reference No'             => '1234567',
        'Date Time'                     => Carbon::today()->format("d/m/Y H:i:s")
    ],

    'testKotakRefundRecon' => [
        'Count'                         => '1',
        'FILE NAME'                     => 'INTERNET',
        'FILE RECEIVED DATE'            =>  Carbon::today()->format("'d-M-y'"),
        'MERCHANT ID'                   => 'OSRAZORPAY',
        'MERCHANT REF NO'               => 'MERCHANT REF NO',
        'FROM APAC'                     => '06410910000362',
        'TO APAC'                       => '06410910000362',
        'PROCESSED FLAG'                => 'Y',
        'AMOUNT'                        => '500',
        'PROCESSED DATE'                => Carbon::today()->format("'d-M-y'"),
        'PROC REMARKS'                  => 'Your funds have been received towards your payment as per details below.',
        'AUTHORIZED BY'                 => 'NET',
        'AUTHORIZED DATE'               => Carbon::today()->format("'d-M-y'"),
        'BANK REF NO'                   => '0006293741',
        'ACTUAL TXN AMOUNT'             => '1000',
        'REFUND MERCHANT REF NO'        => ''
    ],

    'testDlbSuccessRecon' => [
        'Sr.NO'                     => '1',
        'BankMerchantId'            => 'RAZORPG',
        'TxnDate'                   => '20210303',
        'TxnRefNo'                  => '110098534998',
        'BankRefNo'                 => '12345678',
        'PAYMENT_AMT'               => '2.00',
        'AccountNumber'             => '015500100079670',
        'AccountType'               => 'SAVINGS BANK-RESIDENT',
        'IBRefNo'                   => '1234',
    ],

    'testTmbSuccessRecon' => [
        'Merchant Code'             => '1',
        'Merchant TRN'              => 'RAZORPG',
        'Transaction Amount'        => '20210303',
        'Payment Remarks'           => 'test remark',
        'Bank Reference No'         => '12345678',
        'TXN_DATE_TIME '            => '2021',
        'RESPONSE_MESSAGE'          => 'test',
    ],

    'testNsdlSuccessRecon' => [
        'REC_ID'        => '',
        'CHANNELID'     => '',
        'APPID'         => '',
        'PARTNERID'     => '',
        'PGTXNID'       => 'PAYGATE16',
        'MOBILENO'      => '',
        'EMAILID'       => '',
        'ACCOUNTNO'     => '',
        'AMOUNT'        => '500',
        'CURRENCY'      => 'INR',
        'REMARKS'       => '',
        'RESPONSEURL'   => '',
        'REQBYTYPE'     => '',
        'REQBYID'       => '',
        'TXNDATE'       => '2020-12-29 15:39:54.0',
        'PAYMODE'       => '',
        'ADDINFO1'      => '',
        'ADDINFO2'      => '',
        'ADDINFO3'      => '',
        'ADDINFO4'      => '',
        'ADDINFO5'      => '',
        'R_CRE_DT'      => '',
        'STATUS'        => 'S',
        'RESPONSEMSG'   => '',
        'BANKREFNO'     => '1234',
    ],

    'testHdfcSuccessRecon'  =>  [
        'merchant_code'         => 'RAZORPAY',
        'customer_email'        => 'RAZORPAY123',
        'currency'              => 'INR',
        'transaction_amount'    => '500.00',
        'fee'                   => '0.00',
        'payment_id'            => '',
        'error_code'            => '0',
        'bank_payment_id'       => '12345678',
        'transaction_date'      => Carbon::today()->format("Y-m-d H:i:s"),
        'error_description'     => '-'
    ],

    'testBdblSuccessRecon'  => [
        'MerchantReferenceNumber'       => '',
        'BankTransactionReferenceNo'    => '123456',
        'TransactionAmount'             => '500.00',
        'STATUS'                        => '1',
        'TransactionDate'               => Carbon::today()->format("Ymd"),
        'Account_Number'                => '123456',
    ],

    'testSrcbSuccessRecon'  => [
        'Paymentid'                     => '',
        'BankTransactionReferenceNo'    => '1234',
        'TransactionAmount'             => '500.00',
        'Refund_and_narration'          => 'Y',
        'TransactionDate'               => Carbon::today()->format("Ymd"),

    ],
    'testUcoSuccessRecon'  => [
        'Account_No'                    => '123456',
        'PG Payment Reference No(PRN)'  => '',
        'Bank Payment Reference No'     => '123456',
        'Amount'                        => '500.00',
        'Transaction Status'            => '1',
        'Transaction Date(DD-MM-YYYY)'  => Carbon::today()->format("d-m-Y"),

    ],

    'testHdfcCorpSuccessRecon' => [
        'merchant_code'         => 'RAZORPAY',
        'client_code'           => 'RAZORPAY',
        'currency_code'         => 'INR',
        'transaction_amount'    => '500.00',
        'service_change_amount' => '0',
        'merchant_reference_no' => '',
        'status'                => '102',
        'bank_reference_no'     => '12345',
        'transaction_date'      => '06/08/2021' ,
        'error_message'         => '',
    ],

    'testDbsSuccessRecon' => [
        'MERCHANT_ORDER_ID'             => 'MERCHANT_ORDER_ID',
        'TRANSACTION_AMOUNT'            => 'TRANSACTION_AMOUNT',
        'TRANSACTION_REFERENCE_NUMBER'  => 'TRANSACTION_REFERENCE_NUMBER',
        'ORDER_TYPE'                    => 'ORDER_TYPE',
        'TRANSACTION_STATUS'            => 'TRANSACTION_STATUS',
        'TRANSACTION_REQUESTED_DATE'    => 'TRANSACTION_REQUESTED_DATE'
    ],

    'testUjjivanSuccessRecon' => [
        'PRN'                           => '',
        'BID'                           => 'UJ001',
        'AMT'                           => 'AMT',
        'STATUS'                        => 'Y',
        'TXNDATE'                       => Carbon::today()->format("d-m-Y"),
        'ACCOUNTNUMBER'                 => '1234'
    ],
];
