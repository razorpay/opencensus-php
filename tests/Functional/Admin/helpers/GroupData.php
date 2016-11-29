<?php

return [

    'testCreateGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups',
            'method' => 'post',
            'content' => [
                'name' => 'Group1',
                'description' => 'First group',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Group1',
                'description' => 'First group',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testEditGroup' => [
        'request' => [
            'url' => '/orgs/%s/groups/%s',
            'method' => 'put',
            'content' => [
                'name' => 'new name',
                'description' => ' new description'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'new name',
                'description' => ' new description'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMultipleGroups' => [
        'request' => [
            'url' => '/orgs/%s/groups',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [],
                'count' => 2
            ],
            'status_code' => 200,
        ],
    ],

];
