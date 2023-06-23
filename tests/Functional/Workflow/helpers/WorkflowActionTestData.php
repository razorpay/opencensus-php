<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateWorkflowAction' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/admin/%s',
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
            'url'     => '/admin/%s',
            'content' => [
                'name' => 'Test Name',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Other actions on the entity are in progress. Id: %s',
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
                'maker'         => [],
                'maker_type'    => 'admin',
                'current_level' => 1
            ]
        ]
    ],
    'testGetActionsByMakerInternal' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/internal/merchant/RzrpySprAdmnId/w-actions'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'workflow_id'   => 'workflow_workflowId1000',
                        'workflow'      => [
                            'id' => 'workflow_workflowId1000',
                        ],
                        'state'         => 'open',
                        'maker_type'    => 'admin',
                    ],
                ]
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
    'testUpdateWorkflowActionWithTags' => [
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
                        "admin_id"      => "admin_" . \RZP\Tests\Functional\Fixtures\Entity\Org::CHECKER_ADMIN,
                        "approved"      => true,
                        "admin"         => [],
                    ]
                ],
                "comments"          => [],
            ],
        ],
    ],
    'testWorkflowActionApprovedWithComments' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/w-actions/%s/checkers',
            'content'   => [
                'approved'               => 1,
                'approved_with_feedback' => 1,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested action is not found',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND,
        ]
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
                'approved'  => 0
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
                        "admin_id"  => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::CHECKER_ADMIN,
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
                        'admin_id' => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::CHECKER_ADMIN,
                    ],
                    [
                        'approved'  => true,
                        'admin_id'  => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::MAKER_ADMIN,
                    ]
                ]
            ],
        ],
    ],
    'testWorkflowActionExecuteLastApprovalForCredits' => [
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
                        'admin_id' => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::CHECKER_ADMIN,
                    ],
                    [
                        'approved'  => true,
                        'admin_id'  => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::MAKER_ADMIN,
                    ]
                ]
            ],
        ],
    ],
    'testWorkflowActionSuperAdminApprove' => [
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

    'testWorkflowCloseActionWhenEsSyncFails' => [
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
    ],
    'testGetActionsForRiskAudit' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/risk-audit/w-actions',
            'content' => []
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
    'testGetActionsForRiskAuditWithTimeWithinRange' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/risk-audit/w-actions',
            'content' => [
                'approval_start_time'   => time() - 1000,
                'approval_end_time'     => time() + 1000
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
    'testGetActionsForRiskAuditWithTimeOutsideRange' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/risk-audit/w-actions',
            'content' => [
                'approval_start_time'   => '101',
                'approval_end_time'     => '1001'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testWorkflowActionRiskAttributes' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions/%s/risk_attributes',
        ],
        'response' => [
            'content' => [
                'trigger_communication' => '1',
                'risk_tag'	            => 'risk_review_suspend',
                'risk_source'           => 'high_fts',
                'risk_reason'           => 'chargeback_and_disputes',
                'risk_sub_reason'       => 'high_fts',
            ],
        ],
    ],

    'testWorkflowActionUpdateRiskAttributes' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/w-actions/%s/risk_attributes',
            'content' => [
                'trigger_communication' =>'1',
                'risk_tag'	            => 'risk_review_suspend',
                'risk_source'           => 'high_fts',
                'risk_reason'           => 'chargeback_and_disputes',
                'risk_sub_reason'       => 'high_fts',
            ]
        ],
        'response' => [
            'content' => [
                'trigger_communication' => '1',
                'risk_tag'	            => 'risk_review_suspend',
                'risk_source'           => 'high_fts',
                'risk_reason'           => 'chargeback_and_disputes',
                'risk_sub_reason'       => 'high_fts',
            ],
        ],
    ],

    'testWorkflowActionUpdateRiskAttributesIncorrectAttributes' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/w-actions/%s/risk_attributes',
            'content' => [
                'trigger_communication' => '1',
                'risk_tag'	            => 'risk_review_suspend',
                'risk_source'           => 'asdf',
                'risk_reason'           => 'chargeback_and_disputes',
                'risk_sub_reason'       => 'high_fts',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_INVALID_ACTION_RISK_SOURCE',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
