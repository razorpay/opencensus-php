<?php

use RZP\Models\Payment;

return [
    'testEMandateInitialPayment' => [
        'gateway'           => 'netbanking_hdfc',
        'status'            => 'authorized',
        'amount_authorized' => 2000,
        'amount'            => 2000,
        'verified'          => null,
        'late_authorized'   => false,
        'two_factor_auth'   => 'unavailable',
        'auto_captured'     => false,
        'captured'          => false,
        'recurring'         => true,
        'recurring_type'    => Payment\RecurringType::INITIAL,
    ],

    'matchInitiatedToken' => [
        'recurring'                 => false,
        'recurring_status'          => 'initiated',
        'recurring_details'         => [
            'status'            => 'initiated',
            'failure_reason'    => null,
        ],
        'bank'                      => 'HDFC',
        'method'                    => 'netbanking',
        'used_count'                => 1,
        'recurring_failure_reason'  => null,
    ],
];