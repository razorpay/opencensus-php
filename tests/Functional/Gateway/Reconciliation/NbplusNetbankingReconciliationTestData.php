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
];
