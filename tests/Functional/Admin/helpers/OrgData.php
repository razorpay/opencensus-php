<?php

return [

    'testCreateOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'post',
            'content' => [
                'hostname'  => 'hdfc.com',
                'email_domains' => 'hdfc.com,fbapi.com',
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'admin' => [
                    'name' => 'superadmin',
                    'branch_code' => 'a',
                    'employee_code' => 'a',
                    'location_code' => 'a',
                    'department_code' => 'a',
                    'supervisor_code' => 'a',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => [
                    'hdfc.com',
                    'fbapi.com'
                ],
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'email_domains' => 'fbapi.com',
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],

    'testOrgMultiple' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email' => 'sreeram12@gmail.com'
            ],
            'status_code' => 200,
        ],
    ],
];
