<?php
namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use RZP\Tests\Functional\Fixtures\Entity\WorkflowAction;

return [
    'testWorkflowCheckerRequests' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions?duty=checker&type=requested',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'id'                => 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID,
                        'state'             => 'open',
                        'maker_id'          => 'admin_' . Org::SUPER_ADMIN,
                        'maker_type'        => 'admin',
                        'maker'             => [],
                        'approved'          => false,
                        'current_level'     => 1,
                        'permission_name'   => 'edit_admin',
                    ],
                ],
            ],
        ],
    ],
    'testWorkflowMakerRequests' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/w-actions?duty=maker&type=created',
            'content'   => [],
        ],
        'response'      => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'id'                => 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID,
                        'state'             => 'open',
                        'maker_id'          => 'admin_' . Org::SUPER_ADMIN,
                        'maker_type'        => 'admin',
                        'entity_name'       => 'admin',
                        'maker'             => [],
                        'approved'          => false,
                        'current_level'     => 1,
                        'permission_name'   => 'edit_admin',
                    ],
                ],
            ]
        ]
    ],
    'testWorkflowClosedRequests' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/w-actions?duty=maker&type=closed',
            'content'   => [],
        ],
        'response'      => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'state'             => 'closed',
                        'maker_id'          => 'admin_' . Org::MAKER_ADMIN,
                        'maker_type'        => 'admin',
                        'entity_name'       => 'admin',
                        'maker'             => [],
                        'approved'          => false,
                        'current_level'     => 1,
                    ],
                ],
            ]
        ]
    ],
    'testAdminCheckedRequests' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/w-actions?duty=checker&type=created',
            'content'   => [],
        ],
        'response'      => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'state'             => 'open',
                        'maker_id'          => 'admin_' . Org::SUPER_ADMIN,
                        'maker_type'        => 'admin',
                        'entity_name'       => 'admin',
                        'maker'             => [],
                        'approved'          => false,
                        'current_level'     => 2,
                        'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
                    ],
                ],
            ]
        ],
    ],
    'testWorkflowSuperAdminAllRequests' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/v1/w-actions?duty=super&type=all',
            'content'   => [],
        ],
        'response' => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'state'             => 'closed',
                    ],
                    [
                        'state'             => 'open',
                    ]
                ]
            ]
        ]
    ],
    'testWorkflowSuperAdminOpenRequests' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/v1/w-actions?duty=super&type=open',
            'content'   => [],
        ],
        'response' => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'state'             => 'open',
                    ]
                ]
            ]
        ]
    ],

    'testWorkflowSearchByMakerId' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/w-actions?duty=checker&type=requested&maker_id=12345678',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'state'      => 'open',
                        'maker_id'   => 'admin_12345678',
                        'maker_type' => 'admin',
                    ],
                ],
            ]
        ]
    ],
];
