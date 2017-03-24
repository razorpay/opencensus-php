<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map',
            'method' => 'post',
            'content' => [
                'entity_name' => 'org',
                'fields' => [
                    'business_name',
                    'display_name',
                    'auth_type',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity_name' => 'org',
                'fields' => [
                    'business_name',
                    'display_name',
                    'auth_type',
                ],
            ],

            'status_code' => 200,
        ],
    ],

    'testGetFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map/%s',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity_name' => 'org',
                'fields' => [
                    'display_name',
                    'business_name',
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPutFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map/%s',
            'method' => 'put',
            'content' => [
                'fields' => [
                    'business_name',
                    'display_name',
                    'auth_type',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity_name' => 'org',
                'fields' => [
                    'business_name',
                    'display_name',
                    'auth_type',
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map/%s',
            'method' => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
            'status_code' => 200,
        ],
    ],
];
