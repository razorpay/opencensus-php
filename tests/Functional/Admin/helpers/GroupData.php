<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups',
            'method' => 'post',
            'content' => [
                'name' => 'Group1',
                'description' => 'First group',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Group1',
                'description' => 'First group',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testEditGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s',
            'method' => 'put',
            'content' => [
                'name' => 'new name',
                'description' => ' new description'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'new name',
                'description' => ' new description'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMultipleGroups' => [
        'request' => [
            'url' => '/orgs/%s/groups',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [],
                'count' => 2
            ],
            'status_code' => 200,
        ],
    ],

    'testDuplicateGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups',
            'method' => 'post',
            'content' => [
                'description' => 'Some description',
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
        ],
    ],

    'testParentGroupAssignment' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s',
            'method' => 'put',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testAncestorsNotAllowedAsParents' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s/allowed_groups',
            'method' => 'get',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

];
