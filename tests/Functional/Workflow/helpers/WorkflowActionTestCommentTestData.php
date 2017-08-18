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
                'comment' => 'cool comment.'
            ]
        ],
    ],
];