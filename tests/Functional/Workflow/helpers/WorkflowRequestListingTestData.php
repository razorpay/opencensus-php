<?php
namespace RZP\Tests\Functional\Workflow;

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
                        'admin_id'          => 'admin_RzrpySprAdmnId',
                        'admin'             => [],
                        'approved'          => false,
                        'current_level'     => 1,
                        'permission_name'   => 'edit_admin',
                    ],
                ],
            ],
        ],
    ],
];