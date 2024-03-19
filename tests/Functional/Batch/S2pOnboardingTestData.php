<?php

return [

    'testBatchUploadForS2pGroupsOnboarding'   => [
        'request' => [
            'url'  => '/admin/batches',
            'method'  => 'post',
            'content' =>  [
                'type'    => 's2p_groups_onboarding',
            ]
        ],
        'response'  =>    [
            'content' =>  [
                'status'      =>     'CREATED'
            ],
        ],
    ],

    'testBatchUploadForS2pUsersOnboarding'   => [
        'request' => [
            'url'  => '/admin/batches',
            'method'  => 'post',
            'content' =>  [
                'type'    => 's2p_users_onboarding',
            ]
        ],
        'response'  =>    [
            'content' =>  [
                'status'      =>     'CREATED'
            ],
        ],
    ],
];
