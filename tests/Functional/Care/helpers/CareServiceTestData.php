<?php

return [
    'testInternalMerchantFetch' => [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/internal/merchants/10000000000000',
        ],
        'response'  => [
            'content'   => [
                'merchant'  => [
                    'id'            => '10000000000000',
                    'activated'     => false,
                    'hold_funds'    => false,
                ],
            ],
        ],
    ],
];
