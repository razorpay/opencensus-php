<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => [
                'name' => 'Test workflow',
            ],
        ],
        'response' => [
            'content' => [
                'name'   => "Test workflow",
                'levels' => [
                    [
                        'op_type' => 'and',
                        'level'   => 1
                    ]
                ]
            ]
        ],
    ],
    'testDeleteWorkflow' => [
        'request' => [
            'method' => 'DELETE',
            'url'    => '/workflows/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'name' => 'Test workflow',
            ]
        ]
    ],
    'testCreateWorkflowWithPermissionWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => [
                'name' => 'Test workflow',
            ],
        ],
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
        'request' => [
            'method' => 'DELETE',
            'url'    => '/workflows/%s',
            'content' => [],
        ],
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