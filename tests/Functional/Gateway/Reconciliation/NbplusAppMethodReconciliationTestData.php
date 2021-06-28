<?php

use Carbon\Carbon;

return [
    'testTwidSuccessRecon' => [
        'Sr. No'                        => '1',
        'Transaction ID'                => '667788',
        'Merchant Transaction Id'       => 'rzp_p1p23344',
        'Date'                          => Carbon::today()->format("Y-m-d h:m:s"),
        'Brand'                         => 'Demo',
        'Bill Value'                    => '1000',
        'Commission'                    => '50',
        'GST On Commission'             => '10',
        'Total Commission'              => '60',
        'Total Payable'                 => '940',
        'Status'                        => 'Capture',
    ],
];
