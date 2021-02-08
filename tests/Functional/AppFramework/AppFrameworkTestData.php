<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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

    'testCreateWithoutRegistrationRole' => [
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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED
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

    'testCreateAppMappingWithoutMappingRole' => [
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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED
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
