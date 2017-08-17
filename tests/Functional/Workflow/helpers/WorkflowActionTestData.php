<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\Fixtures\Entity\Org;

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
                'entity_id'     => Org::CHECKER_ADMIN,
                'state'         => 'open',
                'org_id'        => 'org_' . Org::RZP_ORG,
                'approved'      => false,
                'current_level' => 1,
            ]
        ],
    ],
];