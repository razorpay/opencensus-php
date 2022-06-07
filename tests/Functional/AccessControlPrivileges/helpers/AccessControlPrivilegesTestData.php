<?php

return [
    'testFetchAllPrivileges' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/access/privileges',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'privilege_data' => [
                    '0' =>
                        array (
                            'id' => '1000privilege1',
                            'name' => 'Account Setting',
                            'description' => 'A/c setting test description',
                            'parent_id' => NULL,
                            'actions' =>
                                array (
                                ),
                            'privilege_data' =>
                                array (
                                    '0' =>
                                        array (
                                            'id' => '1000privilege3',
                                            'name' => 'Business Setting',
                                            'description' => 'Business setting test description',
                                            'parent_id' => '1000privilege1',
                                            'actions' =>
                                                array (
                                                    'view' =>
                                                        array (
                                                            'privilege_id' => '1000privilege3',
                                                            'tooltip' => 'Tooltip',
                                                            'description' => 'Access policy test description',
                                                        ),
                                                    'create' =>
                                                        array (
                                                            'privilege_id' => '1000privilege3',
                                                            'tooltip' => 'Tooltip',
                                                            'description' => 'Access policy test description',
                                                        ),
                                                ),
                                            'tool_tip' => 'PRIVILEGE 3',
                                            'privilege_data' => NULL,
                                        ),
                                    '1' =>
                                        array (
                                            'id' => '1000privilege2',
                                            'name' => 'Tax Setting',
                                            'description' => 'Tax setting test description',
                                            'parent_id' => '1000privilege1',
                                            'actions' =>
                                                array (
                                                    'view' =>
                                                        array (
                                                            'privilege_id' => '1000privilege2',
                                                            'tooltip' => 'Tooltip',
                                                            'description' => 'Access policy test description',
                                                        ),
                                                    'create' =>
                                                        array (
                                                            'privilege_id' => '1000privilege2',
                                                            'tooltip' => 'Tooltip',
                                                            'description' => 'Access policy test description',
                                                        ),
                                                ),
                                            'tool_tip' => 'PRIVILEGE 2',
                                            'privilege_data' => NULL,
                                        ),
                                ),
                        ),
                ]
            ]
        ],
    ],
];
