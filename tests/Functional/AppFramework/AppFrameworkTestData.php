<?php

return [
    'testCreateApp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/app',
            'content' => [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'description' => 'This is test app',
            ],
        ],
    ],

    'testUpdateApp' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'title' => 'Test App updated',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test App',
                'title' => 'Test App updated',
                'type' => 'app',
                'description' => 'This is test app',
            ],
        ],
    ],

    'testGetApp' => [
        'request'  => [
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'description' => 'This is test app',
            ],
        ],
    ],

    'testCreateAppMapping' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/app/mapping',
            'content' => [
                'tag' => 'ecommerce',
                'list' => [],
            ],
        ],
        'response' => [
            'content' => [
                'ecommerce' => 'Tag Mapping Created',
            ],
        ],
    ],

    'testCreateMerchantTag' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'tag' => 'ecommerce',
            ],
        ],
        'response' => [
            'content' => [
                'tag' => 'ecommerce',
            ],
        ],
    ],

    'testDeleteTag' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/app/tags',
            'content' => [
                'tag' => 'ecommerce',
            ],
        ],
        'response' => [
            'content' => [
                'ecommerce' => 'tag mapping deleted',
            ],
        ],
    ],

    'testCreateAppMerchantMapping' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/app/mapping',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'enabled' => true
            ],
        ],
    ],
];
