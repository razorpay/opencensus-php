<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateWorkflow' => [
        'name'   => "Test workflow",
        'levels' => [
            [
                'op_type' => 'and',
                'level'   => 1
            ]
        ]
    ],
    'testDeleteWorkflow' => [
        "name" => "Test workflow",
    ],
    'testCreateWorkflowWithPermissionWorkflow' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One of the permissions already has' .
                                     ' a workflow defined',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS,
        ],
    ],
    'testDeleteWorkflowProgress' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Deleting/Updating a workflow is not' .
                                     ' possible if an action is in still in progress',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED,
        ],
    ],
    'testEditWorkflow' => [
        'name' => 'editing workflow'
    ],
    'testEditWorkflowInProgress' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED,
        ],
    ],
    'testGetWorkflow' => [
        'name'   => "Test workflow",
        'levels' => [
            [
                'op_type' => 'and',
                'level'   => 1
            ]
        ]
    ],
];