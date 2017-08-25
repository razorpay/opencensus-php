<?php
namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\WorkflowAction;

return [
    'testWorkflowCheckerRequests' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions?duty=checker&type=all',
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
                        'admin_id'          => 'admin_' . Org::SUPER_ADMIN,
                        'admin'             => [],
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
            'url'       => '/w-actions?duty=maker&type=maker',
            'content'   => [],
        ],
        'response'      => [
            'content'   => [
                'entity'    => 'collection',
                'items'     => [
                    [
                        'id'                => 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID,
                        'state'             => 'open',
                        'admin_id'          => 'admin_' . Org::SUPER_ADMIN,
                        'entity_name'       => 'admin',
                        'admin'             => [],
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
                'items'     => [
                    [
                        'state'             => 'closed',
                        'admin_id'          => 'admin_' . Org::MAKER_ADMIN,
                        'entity_name'       => 'admin',
                        'admin'             => [],
                        'approved'          => false,
                        'current_level'     => 1,
                    ],
                ],
            ]
        ]
    ],
];