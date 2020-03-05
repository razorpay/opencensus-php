<?php

use RZP\Models\VirtualAccount;

return [
    'dashboard' => [
        'url'       => env('APP_DASHBOARD_URL'),
        'secret'    => env('APP_DASHBOARD_SECRET'),
        'pretend'   => env('APP_DASHBOARD_PRETEND'),
        'cloud'     => true,
    ],

    'dashboard_guest'   => [
        'secret'   => env('APP_DASHBOARD_GUEST_SECRET'),
    ],

    'dashboard_internal' => [
        'secret'   => env('APP_DASHBOARD_INTERNAL_SECRET'),
    ],

    'mock_gateways' => [
        'secret'    => env('MOCK_GATEWAY_SECRET'),
    ],

    'cron' => [
        'secret'    => env('CRON_PASSWORD'),
    ],

    'h2h' => [
        'secret'   => env('APP_H2H_SECRET'),
    ],

    'stork' => [
        'secret' => env('STORK_API_SECRET'),
    ],

    'mailgun' => [
        'url'       => 'razorpay.com',
        'key'       => env('MAILGUN_SECRET'),
        'mock'      => env('MAILGUN_MOCK'),
        'secret'    => env('APP_MAILGUN_SECRET'),
        'from_name' => 'Team Razorpay',
        'from_email' => 'support@razorpay.com'
    ],

    'emi' => [
        'password'            => env('EMI_FILE_PASSWORD'),
        'yesb_encryption_key' => env('YESB_ENCRYPTION_KEY'),
    ],

    'slack' => [
        'team'      => 'razorpay',
        'token'     => env('SLACK_TOKEN'),
        'mock'      => env('SLACK_MOCK'),
    ],

    'sns' => [
        'mock'      => env('SNS_MOCK'),
    ],

    'zapier' => [
        'mock'      => env('ZAPIER_MOCK'),
    ],

    'hosted' => [
        'secret'    => env('APP_HOSTED_SECRET'),
    ],

    'mozart' => [
        'mock'      => env('MOZART_MOCK', false),
        'url'       => env('MOZART_URL'),
        'password'  => env('MOZART_PASSWORD'),
        'username'  => env('MOZART_USERNAME'),

        'test' => [
            'mock'      => env('MOZART_TEST_MOCK', false),
            'url'       => env('MOZART_TEST_URL'),
            'password'  => env('MOZART_TEST_PASSWORD'),
            'username'  => env('MOZART_TEST_USERNAME'),
        ],

        'live' => [
            'mock'     => env('MOZART_LIVE_MOCK', false),
            'url'      => env('MOZART_LIVE_URL'),
            'password' => env('MOZART_LIVE_PASSWORD'),
            'username' => env('MOZART_LIVE_USERNAME'),
        ]
    ],

    'raven' => [
        'mock'      => env('RAVEN_MOCK', false),
        'url'       => env('RAVEN_URL'),
        'secret'    => env('RAVEN_SECRET'),
    ],

    'kyc' => [
        'mock'           => env('KYC_MOCK', false),
        'url'            => env('KYC_URL'),
        'password'       => env('KYC_PASSWORD'),
        'authentication' => env('KYC_AUTH_NAME'),
        'x_service_id'   => env('KYC_SERVICE_ID')
    ],

    'reminders' => [
        'mock'             => env('REMINDERS_MOCK'),
        'url'              => env('REMINDERS_URL'),
        'secret'           => env('REMINDERS_SECRET'),
        'reminder_secret'  => env('REMINDERS_SERVICE_SECRET')
    ],

    'scrooge' => [
        'mock'              => env('SCROOGE_MOCK', false),
        'url'               => env('SCROOGE_URL'),
        'secret'            => env('APP_SCROOGE_SECRET'),
        // TODO: Rename the key!
        // Key and secret through which api will call scrooge
        'scrooge_key'       => env('SCROOGE_KEY'),
        'scrooge_secret'    => env('SCROOGE_SECRET'),
    ],

    'card_vault' => [
        'mock'      => env('CARD_VAULT_MOCK', false),
        'key'       => env('CARD_VAULT_KEY'),
        'secret'    => env('CARD_VAULT_SECRET'),
        'url'       => env('CARD_VAULT_URL'),
    ],

    'cps' => [
        'mock'      => env('CORE_PAYMENT_SERVICE_MOCK', false),
        'username'  => env('CORE_PAYMENT_SERVICE_KEY'),
        'password'  => env('CORE_PAYMENT_SERVICE_SECRET'),
        'url'       => [
            'live' => env('CORE_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('CORE_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'governor' => [
        'mock'      => env('GOVERNOR_SERVICE_MOCK', false),
        'smart_routing' => [
            'username'  => env('GOVERNOR_SMART_ROUTING_SERVICE_KEY'),
            'password'  => env('GOVERNOR_SMART_ROUTING_SERVICE_SECRET'),
        ],
        'cps' => [
            'username'  => env('GOVERNOR_CPS_SERVICE_KEY'),
            'password'  => env('GOVERNOR_CPS_SERVICE_SECRET'),
        ],
        'adminapi' => [
            'username' => env('GOVERNOR_ADMINAPI_SERVICE_KEY'),
            'password' => env('GOVERNOR_ADMINAPI_SERVICE_SECRET'),
        ],
        'url'       => env('GOVERNOR_LIVE_URL'),
    ],

    'redisdualwrite' => [
        'elastic_cache_read'        => env('ELASTIC_CACHE_READ'),
        'skip_dual_write'           => env('SKIP_DUAL_WRITE'),
    ],

    'maxmind' => [
        'mock'      => env('MAXMIND_MOCK', false),
        'id'        => '115820',
        'secret'    => env('MAXMIND_SECRET'),
        'secretv2'  => env('MAXMIND_V2_SECRET')
    ],

    VirtualAccount\Provider::KOTAK => [
        'secret'    => env('KOTAK_SECRET'),
    ],
    VirtualAccount\Provider::YESBANK => [
        'secret'    => env('YESBANK_SECRET'),
    ],

    'rbl' => [
        'secret' => env('BANKING_ACCOUNT_RBL_WEBHOOK_SECRET'),
    ],

    'ecom' => [
        'secret' => env('ECOM_WEBHOOK_SECRET'),
    ],

    'bharatqr' => [
        'secret' => env('BHARAT_QR_SECRET'),
    ],

    'lumberjack' => [
        'url'           => env('LUMBERJACK_URL'),
        'secret'        => env('LUMBERJACK_SECRET'),
        'key'           => env('LUMBERJACK_KEY'),
        'mock'          => env('LUMBERJACK_MOCK', false),
        'identifier'    => env('LUMBERJACK_API_IDENTIFIER'),
        'static_key'    => env('LUMBERJACK_STATIC_KEY'),
    ],

    'harvester' => [
        'url'               => env('HARVESTER_URL'),
        'secret'            => env('HARVESTER_SECRET'),
        'mock'              => env('HARVESTER_MOCK', false),
        'identifier'        => env('HARVESTER_API_IDENTIFIER'),
        'analytics_token'   => env('HARVESTER_ANALYTICS_TOKEN'),
    ],

    'health_check_client' => [
        'mock'              => env('HEALTH_CHECK_CLIENT_MOCK', false),
    ],

    'elfin' => [
        'mock'     => env('ELFIN_MOCK', true),
        'services' => env('ELFIN_SERVICES', 'gimli,bitly'),
        'gimli'    => [
            'secret'   => env('GIMLI_SECRET'),
            'base_url' => env('GIMLI_BASE_URL')
        ],
        'bitly'    => [
            'secret'   => env('BITLY_ACCESS_TOKEN_PUBLIC'),
        ],
        'allow_fallback' => true,
    ],

    'exchange'  => [
        'mock'      => env('EXCHANGE_MOCK', false),
        'url'       => env('EXCHANGE_URL'),
        'appId'     => env('EXCHANGE_APP_ID')
    ],

    'freshdesk' => [
        'url'           => env('FRESHDESK_URL'),
        'sandbox'       => env('FRESHDESK_SANDBOX', false),
        'sandbox_url'   => env('FRESHDESK_SANDBOX_URL'),
        'token'         => env('FRESHDESK_TOKEN'),
        'sandbox_token' => env('FRESHDESK_SANDBOX_TOKEN'),
        'mock'          => env('FRESHDESK_MOCK', false),
    ],

    'zoho' => [
        'header'    => env('ZOHO_HEADER'),
    ],

    'drip' => [
        'mock'      => env('DRIP_MOCK', false),
        'url'       => env('DRIP_URL'),
        'accountId' => env('DRIP_ACCOUNT_ID'),
        'token'     => env('DRIP_TOKEN')
    ],

    'gateway_downtime' => [
        'statuscake' => [
            'username'   => env('STATUSCAKE_USERNAME'),
            'api_key'    => env('STATUSCAKE_API_KEY'),
            'tests_url'  => env('STATUSCAKE_TESTS_URL'),
            'update_url' => env('STATUSCAKE_UPDATE_URL'),
        ]
    ],

    //
    // Configuration for one of the internal applications allowed
    // access to select routes of APIs.
    //
    'auth_service' => [
        'url'       => env('AUTH_SERVICE_URL'),
        'secret'    => env('AUTH_SERVICE_SECRET'),
    ],

    'nodal' => [
        'mock' => env('NODAL_MOCK', false),
        'auth' => [
            'username' => env('NODAL_USERNAME'),
            'password' => env('NODAL_PASSWORD'),
        ],
        'url' => env('NODAL_BASE_URL'),
    ],

    'reporting' => [
        'mock'   => env('REPORTING_MOCK', false),
        'url'    => env('REPORTING_BASE_URL'),
        'username' => 'api',
        'secret' => env('REPORTING_PASSWORD'),
    ],

    'ufh' => [
        'mock' => env('UFH_MOCK', false),
        'url'  => env('UFH_BASE_URL'),
        'auth' => [
            'username' => 'api',
            'password' => env('UFH_PASSWORD'),
        ],
        'admin_auth' => [
            'username' => 'api',
            'password' => env('UFH_ADMIN_PASSWORD'),
        ],
    ],

    'pincodesearch' => [
        'mock'    => env('PINCODE_MOCK', false),
        'url'     => env('PINCODE_BASE_URL'),
        'api_key' => env('PINCODE_SEARCH_API_KEY')
    ],

    'shield' => [
        'mock'    => env('SHIELD_MOCK', false),
        'url'     => env('SHIELD_BASE_URL'),
        'auth' => [
            'username' => 'api',
            'password' => env('SHIELD_SECRET'),
        ],
    ],

    'razorx' => [
        'mock'     => env('RAZORX_MOCK', false),
        'url'      => env('RAZORX_URL'),
        'username' => 'rzp_api',
        'secret'   => env('RAZORX_SECRET'),
    ],

    'user_2fa' => [
        'max_incorrect_tries' => env('USER_2FA_MAX_INCORRECT_TRIES', 9),
    ],

    'kubernetes_client' => [
        'mock'              => env('KUBERNETES_MOCK', false),
        'cluster_url'       => 'https://'.env('KUBERNETES_SERVICE_HOST').':'.env('KUBERNETES_PORT_443_TCP_PORT'),
        'ca_cert'           => env('KUBERNETES_CA_CERT'),
        'token'             => env('KUBERNETES_TOKEN'),
        'namespace'         => env('KUBERNETES_NAMESPACE'),
        'iam_role'          => env('KUBERNETES_IAM_ROLE') ?: env('APP_ENV') . '-api',
        'image_path'        => env('KUBERNETES_IMAGE_PATH'),
        'node_selector'     => env('KUBERNETES_NODE_SELECTOR'),
        'log_path'          => env('KUBERNETES_LOG_PATH'),
        'git_commit_hash'   => env('GIT_COMMIT_HASH', false),
        'app_mode'          => env('APP_MODE'),
        'app_env'           => env('APP_ENV'),
    ],

    'otpelf' => [
        'mock'    => env('OTPELF_MOCK', false),
        'url'     => env('OTPELF_BASE_URL'),
        'api_key' => env('OTPELF_API_KEY'),
    ],

    'beam' => [
        'url'  => env('BEAM_URL'),
        'mock' => env('BEAM_MOCK', false),
    ],

    'subscriptions' => [
        'url'      => env('APP_SUBSCRIPTIONS_URL'),
        'username' => 'rzp',
        'secret'   => env('APP_SUBSCRIPTIONS_SECRET'),
    ],

    'offline_verification' => [
        'url'           => env('APP_OFFLINE_VERIFICATION_URL'),
        'username'      => 'api',
        'secret'        => env('APP_OFFLINE_VERIFICATION_SECRET'),
        'timeout'       => env('APP_OFFLINE_VERIFICATION_TIMEOUT', 60),
    ],

    'payment_links' => [
        'secret'   => env('APP_PAYMENT_LINKS_SECRET'),
    ],

    'myoperator' => [
        'mock'      => env('MYOPERATOR_MOCK'),
        'api_token' => env('MYOPERATOR_API_TOKEN'),
    ],

    'banking_service_url' => env('BANKING_SERVICE_URL', 'https://x.razorpay.com'),

    'payout_links' => [
            'url' => env('APP_PAYOUT_LINKS_URL', 'https://payout-links.razorpay.com')
        ],

    'vajra' => [
        'secret'   => env('APP_VAJRA_SECRET'),
    ],

    'fts' => [
        'mock'   => env('FTS_MOCK', false),
        'secret' => env('APP_FTS_SECRET'),
        'test'   => [
            'url'                     => env('FTS_URL_TEST'),
            'fts_key'                 => env('FTS_KEY_TEST'),
            'fts_secret'              => env('FTS_SECRET_TEST'),
            'fts_dashboard_key'       => env('FTS_DASHBOARD_KEY_TEST'),
            'fts_dashboard_secret'    => env('FTS_DASHBOARD_SECRET_TEST'),
        ],
        'live'   => [
            'url'                     => env('FTS_URL_LIVE'),
            'fts_key'                 => env('FTS_KEY_LIVE'),
            'fts_secret'              => env('FTS_SECRET_LIVE'),
            'fts_dashboard_key'       => env('FTS_DASHBOARD_KEY_LIVE'),
            'fts_dashboard_secret'    => env('FTS_DASHBOARD_SECRET_LIVE'),
        ],
    ],

    'batch' => [
        'secret'            => env('BATCH_API_SECRET'),
        'mock'              => env('BATCH_MOCK',false),
        'url'               => env('BATCH_SERVICE_URL'),
        'username'          => env('BATCH_USERNAME'),
        'password'          => env('BATCH_PASSWORD'),
    ],

    'smart_routing' => [
        'url'       => env('SMART_ROUTING_URL'),
        'mock'      => env('SMART_ROUTING_MOCK',false),
        'username'  => env('SMART_ROUTING_USERNAME'),
        'password'  => env('SMART_ROUTING_PASSWORD')
    ],

    'doppler' => [
        'mock'      => env('DOPPLER_MOCK'),
        'topic'     => env('DOPPLER_SNS_TOPIC'),
        'url'       => env('DOPPLER_LIVE_URL'),
        'key'       => env('DOPPLER_KEY'),
        'secret'    => env('DOPPLER_API_SECRET')
    ],

    'non_blocking_http' => [
        'timeout'       => env('NON_BLOCKING_HTTP_TIMEOUT')
    ],

    'hubspot' => [
        'mock'     => env('HUBSPOT_MOCK', false),
        'url'      => env('HUBSPOT_URL'),
        'secret'   => env('HUBSPOT_SECRET'),
    ],

    'hyper_verge' => [
        'url'     => env('HYPERVERGE_URL'),
        'app_id'  => env('HYPERVERGE_APP_ID'),
        'app_key' => env('HYPERVERGE_APP_KEY'),
        'mock'    => env('HYPERVERGE_MOCK', false),
    ],

    'mtu_lambda' => [
        'secret'        => env('MTU_LAMBDA_SECRET'),
    ],

    'card_payment_service' => [
        'mock'      => env('CARD_PAYMENT_SERVICE_MOCK', false),
        'username'  => env('CARD_PAYMENT_SERVICE_KEY'),
        'password'  => env('CARD_PAYMENT_SERVICE_SECRET'),
        'url'       => [
            'live' => env('CARD_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('CARD_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'nbplus_payment_service' => [
        'mock'      => env('NBPLUS_PAYMENT_SERVICE_MOCK', false),
        'username'  => env('NBPLUS_PAYMENT_SERVICE_KEY'),
        'password'  => env('NBPLUS_PAYMENT_SERVICE_SECRET'),
        'url'       => [
            'live' => env('NBPLUS_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('NBPLUS_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'automation' => [
        'secret' => env('AUTOMATION_API_SECRET'),
    ],

    'salesforce' => [
        'mock'          => env('SALESFORCE_MOCK', false),
        'url'           => env('SALESFORCE_URL'),
        'username'      => env('SALESFORCE_USERNAME'),
        'password'      => env('SALESFORCE_PASSWORD'),
        'client_id'     => env('SALESFORCE_CLIENT_ID'),
        'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
    ],

    'terminals_service' => [
        'mock'          => env('TERMINALS_SERVICE_MOCK', false),
        'live'          => [
            'password'      => env('TERMINALS_SERVICE_LIVE_PASSWORD'),
            'url'           => env('TERMINALS_SERVICE_LIVE_URL'),
        ],
        'test'          => [
            'password'      => env('TERMINALS_SERVICE_TEST_PASSWORD'),
            'url'           => env('TERMINALS_SERVICE_TEST_URL'),
        ],

    ],

    'typeform' => [
        'typeform_webhook_secret'  => env('TYPEFORM_WEBHOOK_SECRET'),
        'typeform_encryption_algo' => env('TYPEFORM_ENCRYPTION_ALGO'),
    ]
];
