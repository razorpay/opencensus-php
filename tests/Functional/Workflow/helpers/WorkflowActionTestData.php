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
    'testWorkflowActionDiff' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions/%s/diff',
            'content' => []
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],
    'testWorkflowActionApproveL1' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'      => 1,
            ]
        ],
        'response' => [
            'content' => [
                "state"             => "open",
                "current_level"     => 2,
                "checkers"          => [
                    [
                        "admin_id"      => "admin_" . \RZP\Tests\Functional\Fixtures\Entity\Org::SUPER_ADMIN,
                        "approved"      => true,
                        "admin"         => [],
                    ]
                ],
                "comments"          => [],
            ],
        ],
    ],
    'testWorkflowClosedActionApproveOrRejectShouldFail' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'  => 1
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Workflow action is not in any open state',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACTION_NOT_IN_OPEN_STATES,
        ]
    ],
    'testWorkflowActionApproveDiffRole' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'      => 1,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No check required from checker roles in the current workflow action level',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL,
        ],
    ],
    'testWorkflowActionRejection' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'  => 0,
            ]
        ],
        'response' => [
            'content' => [
                "state"         => "rejected",
                "approved"      => false,
                "current_level" => 1,
                "checkers"      => [
                    [
                        "approved"  => false,
                        "admin_id"  => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::SUPER_ADMIN,
                    ]
                ]
            ],
        ],
    ],
    'testWorkflowActionExecuteLastApproval' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'  => 1,
            ],
        ],
        'response' => [
            'content' => [
                'state'     => 'executed',
                'checkers'  => [
                    [
                        'approved' => true,
                        'admin_id' => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::SUPER_ADMIN,
                    ],
                    [
                        'approved'  => true,
                        'admin_id'  => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::MAKER_ADMIN,
                    ]
                ]
            ],
        ],
    ],
    'testWorkflowCloseAction' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/w-actions/close/%s',
        ],
        'response' => [
            'content' => [
                "state"         => "closed",
                "approved"      => false,
            ]
        ]
    ],
    'testWorkflowCanOnlyBeClosedByMaker' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/w-actions/close/%s',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'An action can only be closed by maker',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_CLOSE_UNAUTHORIZED,
        ],
    ]
];