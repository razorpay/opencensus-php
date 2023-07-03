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
        'accounting_integrations_proxy_routes' => ['host_id' => 'accounting_integrations'],
        'accounting_integrations_callback'     => ['host_id' => 'accounting_integrations_direct_api'],
        'accounting_integrations_admin_routes' => ['host_id' => 'accounting_integrations_admin_api'],
        'wallet_dashboard_proxy'               => ['host_id' => 'wallet'],
        'partnerships_service_proxy'           => ['host_id' => 'partnerships'],

        // disputes service proxy routes
        'dispute_ingestion'                => ['host_id' => 'disputes'],
        'dispute_dcs_config_add'           => ['host_id' => 'disputes'],
        'dispute_dcs_config_get'           => ['host_id' => 'disputes'],
        'dispute_dcs_config_update'        => ['host_id' => 'disputes'],
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
        ],

        'accounting_integrations' => [
            'host'                => env('ACCOUNTING_INTEGRATIONS_HOST_URL'),
            'auth'                => ['api', env('ACCOUNTING_INTEGRATIONS_PASSWORD')],
            'path_prefix_to_skip' => 'v1/accounting-integrations/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'accounting_integrations_direct_api' => [
            'host'                => env('ACCOUNTING_INTEGRATIONS_HOST_URL'),
            'auth'                => ['api_direct', env('ACCOUNTING_INTEGRATIONS_DIRECT_PASSWORD')],
            'path_prefix_to_skip' => 'v1/direct/accounting-integrations/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'accounting_integrations_admin_api' => [
            'host'                => env('ACCOUNTING_INTEGRATIONS_HOST_URL'),
            'auth'                => ['api_admin', env('ACCOUNTING_INTEGRATIONS_ADMIN_PASSWORD')],
            'path_prefix_to_skip' => 'v1/accounting-integrations/admin/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'wallet' => [
            'host'                => env('APP_WALLET_LIVE_URL'),
            'test_host'           => env('APP_WALLET_TEST_URL'),
            'auth'                => ['', ''],
            'path_prefix_to_skip' => 'v1/wallet/proxy/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'disputes' => [
            'host'               => env('DISPUTES_BASE_URL'),
            'auth'                => ['api', env('DISPUTES_API_SECRET')],
            'path_prefix_to_skip' => 'v1/disputes/',
            'path_prefix_to_add'  => 'v1/',
        ],

        'partnerships' => [
            'host'                => env('PARTNERSHIPS_LIVE_URL'),
            'auth'                => ['api', env('PARTNERSHIPS_API_SECRET')],
            'path_prefix_to_skip' => 'v1/partnerships/',
            'path_prefix_to_add'  => '',
        ],
    ],
];
