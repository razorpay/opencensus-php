<?php

use RZP\Gateway\Upi\Icici\Fields;

return [
    'upiIcici' => [
        'accountNumber'   => '000205025290',
        'merchantID'      => '116798',
        'merchantName'    => 'RAZORPAY',
        'subMerchantID'   => '116798',
        'subMerchantName' => 'Razorpay SUB',
        'merchantTranID'  => 'EqLhm2zHgYQ1Mx',
        'bankTranID'      => '734122607521',
        'date'            => '03/07/2020',
        'time'            => '08:27 PM',
        'amount'          =>  500,
        'payerVA'         => '9619218329@ybl',
        'status'          => 'SUCCESS',
        'Commission'      => '0',
        'Net amount'      => '0',
        'Service tax'     => '0',
    ],

    'testProcessIciciQrPayment' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '500.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],
];
