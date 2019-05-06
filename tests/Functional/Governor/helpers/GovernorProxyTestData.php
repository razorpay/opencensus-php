<?php

return [
    'testCreateNamespace' => [
        'request' => [
            'content' => [
                'key'     => 'value',
            ],
            'url' => '/cps/rule_engine/namespace',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "status"    =>  false,
                "error"     => "SOME_AWESOME_ERROR"
            ],
            'status_code'   => 400,
        ],
    ],
];
