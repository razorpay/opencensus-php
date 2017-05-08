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

    'testInvalidFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map',
            'method' => 'post',
            'content' => [
                'entity_name' => 'org',
                'fields' => [
                    'business_name',
                    'display_name',
                    'invalid_field',
                ],
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'error_description' => 'Few fields are invalid for the given entity',
        ],
    ],

    'testInvalidEntityForFieldMap' => [
        'request' => [
            'url' => '/orgs/%s/field-map',
            'method' => 'post',
            'content' => [
                'entity_name' => 'invalid_entity',
                'fields' => [
                    'business_name',
                    'display_name',
                    'invalid_field',
                ],
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'error_description' => 'The entity name is not registered in the api',
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

    'testFieldMapMultiple' => [
        'request' => [
            'url' => '/orgs/%s/field-map',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetFieldMapByEntity' => [
        'request' => [
            'url' => '/orgs/%s/field-map/entity/%s',
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

    'testCreateFieldMapForPasswordAuth' => [
        'request' => [
            'url' => '/orgs/%s/field-map',
            'method' => 'post',
            'content' => [
                'entity_name' => 'admin',
                'fields' => [
                    'password',
                    'password_confirmation',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity_name' => 'admin',
                'fields' => [
                    'password',
                    'password_confirmation',
                ],
            ],

            'status_code' => 200,
        ],
    ],
];
