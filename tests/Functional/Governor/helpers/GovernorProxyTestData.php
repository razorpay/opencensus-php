<?php
return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cps/namespaces/1',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "id" => "",
                "name" => "",
                "description" => "",
                "total_rule_chains" => 0,
                "created_by" => "me"
            ],
            'status_code'   => 400,
        ],
    ],
];
