<?php

return [
    'testExtractReport' => [
        'request' => [
            'url'       => '/freshchat/extract_report',
            'method'    => 'POST',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code'   => 200,
        ],
    ],

    'testRetrieveReport' => [
        'request' => [
            'url'       => '/freshchat/retrieve_report/',
            'method'    => 'POST',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code'   => 200,
        ],
    ],
];
