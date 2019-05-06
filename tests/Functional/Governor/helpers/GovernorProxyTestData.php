<?php

return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'raw' => json_encode([
                'key'     => 'value',
                'q'         => "1",
            ]),
            'url' => '/cps/rule_engine/execute/rule_chain/namespace?q1=1',
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
