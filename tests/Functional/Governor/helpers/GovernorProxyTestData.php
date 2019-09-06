<?php
return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cps/clients/1/namespaces',
            'raw' => json_encode([
                "name"=> "namespace2",
                "description" => "yo",
                "created_by" => "me",
                "domain_model"=> [
                    "entities" => [[
                        "name"=> "yo",
                        "type"=> "object",
                        "sub_type"=> null,
                        "default_value" => null,
                        "values"=> null,
                        "score"=> null,
                        "attributes" => [[
                            "name"=> "method",
                            "type"=> "string",
                            "default_value"=> null,
                            "values"=> ["card", "emi"],
                            "score"=> 0,
                            "index"=> "true"
                        ]]
                    ]],
                    "evaluating_entity" => [
                        "name"=> "yo",
                        "type"=> "list",
                        "sub_type"=> "object",
                        "default_value"=> null,
                        "values"=> null,
                        "score"=> null,
                        "attributes" => [[
                            "name"=> "method",
                            "type"=> "string",
                            "default_value"=> "",
                            "score"=> 0,
                            "index"=> "false"
                        ]]
                    ]
                ]
            ]),
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
