<?php

return [
    'testCreateSubBalance' => [
        'request'  => [
            'url'     => '/create_sub_balance',
            'method'  => 'post',
            'content' => [
                'parent_balance_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                'sub_balance' =>[],
                'sub_balance_map' => []
            ]
        ],
    ],
];
