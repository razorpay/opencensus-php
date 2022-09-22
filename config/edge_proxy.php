<?php
// Ref \RZP\Http\Controllers\EdgeProxyController.

return [
    // Map- <Api's route name, <Host identifier>>
    'route_config' => [
        // 'example' => ['host_id' => 'example_host'],
        'metro_project_create'             => ['host_id' => 'metro'],
        'metro_project_credentials_create' => ['host_id' => 'metro'],
        'metro_project_topic_update'       => ['host_id' => 'metro'],
        'accounts_receivable_all_routes'   => ['host_id' => 'accounts_receivable'],
        'business_reporting_all_proxy_routes' => ['host_id' => 'business_reporting'],
    ],

    // Map- <Host identifier, <Host, Auth[username, password]>>
    'host_config'  => [
        // 'example_host' => ['host' => 'example.com', 'auth' => ['username', 'password']],
        'metro' => [
            'host'                => env('METRO_HOST_URL'),
            'auth'                => ['admin', env('METRO_ADMIN_PASSWORD')],
            'path_prefix_to_skip' => 'v1/metro/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'accounts_receivable' => [
            'host'                => env('ACCOUNTS_RECEIVABLE_HOST_URL'),
            'auth'                => ['api', env('ACCOUNTS_RECEIVABLE_PASSWORD')],
            'path_prefix_to_skip' => 'v1/accounts-receivable/service/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'business_reporting' => [
            'host'                => env('BUSINESS_REPORTING_HOST_URL'),
            'auth'                => ['api', env('BUSINESS_REPORTING_PASSWORD')],
            'path_prefix_to_skip' => 'v1/business-reporting/',
            'path_prefix_to_add'  => 'v1/',
        ]
    ],
];
