<?php

use Carbon\Carbon;

return [
    'testWalnut369SuccessRecon' => [
        'Merchant Name'                 => 'Merchant name',
        'Merchant Id'                   => 'Merchant Id',
        'Date of txn'                   => Carbon::today()->format("Ymd"),
        'Purchased or Cancelled Amount' => '500.00',
        'Txn Type'                      => 'Disbursal',
        'UTR'                           => 'UTR',
        'RZP Txn ID'                    => '',
        'finalizeChargeTransactionId'   => 'gateway_payemnt_id',
        'Tenure'                        => '1',
        'Subvention Amount'             => '1',
        'MDR'                           => '1',
        'Partner Fees'                  => '1',
        'Net Transfer Amount'           => '110',
    ],
];
