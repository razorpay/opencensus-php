<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

return [
    'testPayment' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => '9999999999',
        'status'          => 'Y',
        'reference1'      => 'RazorpayPay',
        'received'        => true,
        'error_message'   => 'Payment successful'
    ],
];
