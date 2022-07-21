<?php

use Carbon\Carbon;

return [
    'testPhonepeSuccessRecon' => [
        'Paymenttype'           => 'PAYMENT',
        'MerchantReferenceId'   => 'JmQkGGH7',
        'MerchantOrderId'       => 'JmQkGGH7',
        'PhonePeReferenceId'    => 'T2206271',
        'From'                  => 'PhonePe Private Limited',
        'CreationDate'          => Carbon::today()->format('d-m-Y'),
        'TransactionDate'       => Carbon::today()->format('d-m-Y'),
        'SettlementDate'        => Carbon::tomorrow()->format('d-m-Y'),
        'BankReferenceNo'       => 'T2206271',
        'Amount'                => '500.00',
        'Fee'                   => '-10',
        'IGST'                  => '0',
        'CGST'                  => '-0.1',
        'SGST'                  => '-0.1'
    ],

];
