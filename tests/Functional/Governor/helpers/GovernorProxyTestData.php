<?php

return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cps/rule_engine/rule/namespace/entity_identifier1',
            'method' => 'GET'
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
