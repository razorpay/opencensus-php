<?php

return [
    'callScroogeFetchEntities' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/entities',
            'content' => [
                'refund_ids' => [
                    'dummy1',
                    'dummy2',
                ],
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
