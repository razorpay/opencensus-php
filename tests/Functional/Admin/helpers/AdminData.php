<?php

return [

    'testCreateAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'test_admin',
                'email'              => 'xyz@abc.com',
                'username'           => 'harshil',
                'password'           => 'test123456',
                'remember_token'     => 'yes',
                'oauth_access_token' => 'oauth123',
                'oauth_provider_id'  => 'google',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test_admin',
                'email'              => 'xyz@abc.com',
                'username'           => 'harshil',
                'password'           => 'test123456',
                'remember_token'     => 'yes',
                'oauth_access_token' => 'oauth123',
                'oauth_provider_id'  => 'google',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
            'status_code' => 200,
        ]
    ],

    'testEditAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'test',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name' => 'test_admin',
                'email' => 'xyz@abc.com',
                'username' => 'harshil',
            ],
            'status_code' => 200,
        ],
    ],


];
