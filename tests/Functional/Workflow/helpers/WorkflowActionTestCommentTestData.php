<?php

return [
    'testWorkflowCreateComment' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/w-actions/%s/comments',
            'content' => [
                'comment' => 'cool comment.',
            ]
        ],
        'response' => [
            'content' => [
                'comment'  => 'cool comment.',
                'admin_id' => 'admin_' . \RZP\Tests\Functional\Fixtures\Entity\Org::SUPER_ADMIN,
            ]
        ],
    ],
];
