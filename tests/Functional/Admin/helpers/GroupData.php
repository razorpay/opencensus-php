<?php

return [

    'testAddGroupsOnMerchant' => [
        'request' => [
            'url'       => '/merchants/%s',
            'method'    => 'put',
            'content'   => []
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],
];
