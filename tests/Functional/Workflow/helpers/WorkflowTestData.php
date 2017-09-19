<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

use RZP\Tests\Functional\Fixtures\Entity\Workflow;

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
                        'op_type' => 'or',
                        'level'   => 1
                    ]
                ]
            ]
        ],
    ],
    'testDeleteWorkflow' => [
        'request' => [
            'method'  => 'DELETE',
            'url'     => '/workflows/%s',
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS,
        ],
    ],
    'testDeleteWorkflowProgress' => [
        'request' => [
            'method'  => 'DELETE',
            'url'     => '/workflows/%s',
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED,
        ],
    ],
    'testEditWorkflow' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/workflows/%s',
            'content' => [
                'name' => 'editing workflow',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'editing workflow',
            ],
        ],
    ],
    'testEditWorkflowInProgress' => [
        'request' => [
            'method' => 'PUT',
            'url'    => '/workflows/%s',
            'content' => [
                'name' => 'editing workflow',
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED,
        ],
    ],
    'testGetWorkflow' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/workflows/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'levels' => [
                    [
                        'op_type' => 'or',
                        'level'   => 1
                    ],
                ],
            ],
        ],
    ],
    'testWorkflowGetMultiple' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/workflows',
            'content'   => [],
        ],
        'response'  => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'id'    => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID
                    ]
                ]
            ]
        ]
    ],
];