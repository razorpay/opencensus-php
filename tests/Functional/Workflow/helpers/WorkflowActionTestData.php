<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateWorkflowAction' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/orgs/%s/admins/%s',
            'content' => [
                'name' => 'Test Name',
            ]
        ],
        'response' => [
            'content' => [
                'entity_name'   => 'admin',
                'state'         => 'open',
                'approved'      => false,
                'current_level' => 1,
            ]
        ],
    ],
    'testCreateWorkflowActionInprogress' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/orgs/%s/admins/%s',
            'content' => [
                'name' => 'Test Name',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Other actions on the entity are in progress.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS,
        ],
    ],
    'testGetWorkflowActionDetails' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/w-actions/%s/details',
            'content' => []
        ],
        'response' => [
            'content' => [
                'permission'    => [],
                'checkers'      => [],
                'comments'      => [],
                'workflow_id'   => 'workflow_workflowId1000',
                'workflow'      => [
                    'id' => 'workflow_workflowId1000',
                ],
                'state'         => 'open',
                'admin'         => [],
                'current_level' => 1
            ]
        ]
    ],
    'testUpdateWorkflowAction' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/w-actions/%s',
            'content' => [
                'title'       => 'Test Workflow Action Title.',
                'description' => 'Test Workflow Action description.',
            ]
        ],
        'response' => [
            'content' => [
                'title'         => 'Test Workflow Action Title.',
                'description'   => 'Test Workflow Action description.',
                'current_level' => 1,
            ]
        ],
    ],
];