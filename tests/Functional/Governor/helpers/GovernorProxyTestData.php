<?php
return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/clients',
            'raw' => json_encode([
                        "name"=> "namespace23",
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
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    "id" => "cps",
                    "name" => "cps",
                ],
                [
                    "id" => "routingengine",
                    "name" => "routingengine"
                ]

            ],
            'status_code'   => 200,
        ],
    ],
];
