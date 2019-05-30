<?php

return [
    'testCreateBankingAccount' => [
        'request' => [
            'url' => '/banking_account',
            'method' => 'POST',
            'content' => [
                'bank'          => 'rbl',
                'pincode'       => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'bank'        => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],
];
