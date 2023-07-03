<?php

use RZP\Models\VirtualAccount;

return [
    'dashboard' => [
        'url'       => env('APP_DASHBOARD_URL'),
        'secret'    => env('APP_DASHBOARD_SECRET'),
        'pretend'   => env('APP_DASHBOARD_PRETEND'),
        'cloud'     => true,
    ],

    'admin_dashboard' => [
        'secret'    => env('APP_ADMIN_DASHBOARD_SECRET'),

    ],

    'merchant_dashboard' => [
        'secret'    => env('APP_MERCHANT_DASHBOARD_SECRET'),
    ],

    'dashboard_guest'   => [
        'secret'   => env('APP_DASHBOARD_GUEST_SECRET'),
    ],

    'dashboard_internal' => [
        'secret'   => env('APP_DASHBOARD_INTERNAL_SECRET'),
    ],

    'frontend_graphql' => [
        'secret'   => env('APP_FRONTEND_GRAPHQL_SECRET'),
    ],

    'mock_gateways' => [
        'secret'    => env('MOCK_GATEWAY_SECRET'),
    ],

    'razorpayx_client' => [
        'test' => [
            'mock' => env('ONDEMAND_X_MOCK_TEST', true),
            'mock_webhook' => env('ONDEMAND_X_WEBHOOK_TEST', true),
        ],
        'live' => [
            'mock' => env('ONDEMAND_X_MOCK_LIVE', false),
            'mock_webhook' => env('ONDEMAND_X_WEBHOOK_LIVE', false),
            'razorpayx_url' => env('RAZORPAYX_URL_LIVE'),
            'ondemand_x_merchant' => [
                'id'                => env('ONDEMAND_X_MERCHANT_LIVE_ID'),
                'username'          => env('ONDEMAND_X_MERCHANT_LIVE_USER_NAME'),
                'secret'            => env('ONDEMAND_X_MERCHANT_LIVE_SECRET'),
                'account_number'    => env('ONDEMAND_X_MERCHANT_LIVE_ACCOUNT_NUMBER'),
                'webhook_key'       => env('ONDEMAND_X_MERCHANT_LIVE_WEBHOOK_KEY'),
            ],
            'ondemand_contact' => [
                'fund_account_id' => env('ONDEMAND_CONTACT_FUND_ACCOUNT_ID'),
            ],
        ]
    ],

    'merchants-risk' => [
        'secret'    => env('MERCHANTS_RISK_SECRET'),
    ],

    'express' => [
        'secret'    => env('EXPRESS_SECRET'),
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

    'cardsettlement' => [
        'axis_encryption_key' => env('AXIS_ENCRYPTION_KEY'),
    ],

    'downtime_service_slack' => [
        'token' => env('DOWNTIME_SERVICE_SLACK_TOKEN'),
        'mock'  => env('DOWNTIME_SERVICE_SLACK_MOCK'),
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
        'mock'                             => env('MOZART_MOCK', false),
        'url'                              => env('MOZART_URL'),
        'secret'                           => env('MOZART_PASSWORD'),
        'password'                         => env('MOZART_PASSWORD'),
        'username'                         => env('MOZART_USERNAME'),
        'cred_eligibility_request_timeout' => env('CRED_ELIGIBILITY_REQUEST_TIMEOUT', .18),
        'ccavenue_collect_request_timeout' => env('CCAVENUE_COLLECT_REQUEST_TIMEOUT', 300),
        'optrzp_collect_request_timeout'   => env('OPTRZP_COLLECT_REQUEST_TIMEOUT', 300),

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
        ],

        'live_whitelisted' => [
            'mock'     => env('MOZART_WHITELISTED_LIVE_MOCK', false),
            'url'      => env('MOZART_WHITELISTED_LIVE_URL'),
            'password' => env('MOZART_LIVE_PASSWORD'),
            'username' => env('MOZART_LIVE_USERNAME'),
            ]
    ],

    'raven' => [
        'mock'      => env('RAVEN_MOCK', false),
        'url'       => env('RAVEN_URL'),
        'secret'    => env('RAVEN_SECRET'),
    ],

    'reminders' => [
        'mock'             => env('REMINDERS_MOCK'),
        'url'              => env('REMINDERS_URL'),
        'secret'           => env('REMINDERS_SECRET'),
        'reminder_secret'  => env('REMINDERS_SERVICE_SECRET')
    ],

    'thirdwatch_reports' => [
        'secret'  => env('THIRDWATCH_REPORTS_SERVICE_SECRET')
    ],

    'thirdwatch' => [
        'secret'  => env('THIRDWATCH_SERVICE_SECRET')
    ],

    'thirdwatch_cod_score' => [
      'secret'  => env('THIRDWATCH_COD_SCORE_SERVICE_SECRET')
    ],

    'xpayroll' => [
        // the secret used by the Opfin to call apis under internal auth
        // this same secret is used as the password to call APIs on the micro-service
        'secret'  => env('OPFIN_SERVICE_SECRET'),
        'baseUrl' => env('OPFIN_SERVICE_URL')
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

    'razorflow' => [
        'mock'              => env('RAZORFLOW_MOCK', false),
        'url'               => env('RAZORFLOW_URL'),
        'secret'            => env('APP_RAZORFLOW_SECRET'),

        // Key and secret through which api will call Razorflow
        'razorflow_key'     => env('RAZORFLOW_KEY'),
        'razorflow_secret'  => env('RAZORFLOW_SECRET'),

        // For verifying requests from slack
        'razorflow_slack_signing_secret' => env('RAZORFLOW_SLACK_SIGNING_SECRET'),
    ],

    'cache_dual_write' => [
        'cluster_cache_read'        => env('CLUSTER_CACHE_READ'),
        'cache_skip_dual_write'     => env('CACHE_SKIP_DUAL_WRITE'),
    ],

    'card_vault' => [
        'mock'             => env('CARD_VAULT_MOCK', false),
        'key'              => env('CARD_VAULT_KEY'),
        'secret'           => env('CARD_VAULT_SECRET'),
        'mpan_key'         => env('CARD_VAULT_MPAN_KEY'),
        'mpan_secret'      => env('CARD_VAULT_MPAN_SECRET'),
        'razorpayx_key'    => env('CARD_VAULT_RAZORPAYX_KEY'),
        'razorpayx_secret' => env('CARD_VAULT_RAZORPAYX_SECRET'),
        'url'              => env('CARD_VAULT_URL'),
        'key_id'           => env('CARD_KMS_KEY_ID'),
        'region'           => env('AWS_REGION'),
        'version'          => env('CARD_KMS_VERSION'),
        'kms_mock'         => env('CARD_KMS_MOCK'),
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

    'barricade' => [
        'secret'  => env('BARRICADE_SERVICE_SECRET'),
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
        'redislab_cache_read'      => env('REDISLABS_CACHE_READ'),
        'skip_dual_write'          => env('SKIP_DUAL_WRITE'),
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
    VirtualAccount\Provider::ICICI => [
        'secret'    => env('ICICI_SECRET'),
    ],
    VirtualAccount\Provider::HDFC_ECMS => [
        'secret'    => env('HDFC_ECMS_SECRET'),
    ],
    'hdfc_otc' => [
        'secret'    => env('HDFC_OTC_SECRET'),
    ],

    'rbl_va'    => [
        'org_token' => env('RBL_VA_SECRET'),
    ],

    'rbl' => [
        'secret' => env('BANKING_ACCOUNT_RBL_WEBHOOK_SECRET'),
    ],

    'ecom' => [
        'secret' => env('ECOM_WEBHOOK_SECRET'),
    ],
    'perfios' => [
        'secret' => env('FINANCIAL_DATA_SERVICE_PERFIOS_WEBHOOK_SECRET'),
    ],

    'los' => [
        'secret' => env('LOS_PASSWORD'),
    ],

    'loc' => [
        'secret' => env('LOC_PASSWORD'),
    ],

    'capital_cards_client' => [
        'secret' => env('CAPITAL_CARDS_PASSWORD'),
    ],

    'capital_scorecard_client' => [
        'secret' => env('CAPITAL_SCORECARD_PASSWORD'),
    ],

    'capital_collections_client' => [
        'secret' => env('CAPITAL_COLLECTIONS_PASSWORD'),
    ],
    'capital_cards_m2p' => [
        'secret' => env('CAPITAL_CARDS_M2P_WEBHOOK_SECRET','api')
    ],
    'capital_early_settlements' => [
        'secret' => env('CAPITAL_ES_PASSWORD')
    ],
    'leegality' => [
        'secret' => env('LEEGALITY_WEBHOOK_SECRET')
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

    'harvester_v2' => [
        'url'               => env('HARVESTER_V2_URL'),
        'mock'              => env('HARVESTER_MOCK', false),
        'analytics_token'   => env('HARVESTER_ANALYTICS_V2_TOKEN'),
        'identifier'        => env('HARVESTER_API_IDENTIFIER'),
    ],

    'health_check_client' => [
        'mock'              => env('HEALTH_CHECK_CLIENT_MOCK', false),
    ],

    'elfin' => [
        'mock'     => env('ELFIN_MOCK', true),
        'services' => env('ELFIN_SERVICES', 'gimli,bitly'),
        'gimli'    => [
            'secret'    => env('GIMLI_SECRET'),
            'base_url'  => env('GIMLI_BASE_URL'),
            'short_url' => env('GIMLI_SHORT_URL'),
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

    'freshchat' => [
        'base_url'  => env('FRESHCHAT_URL'),
        'token'     => env('FRESHCHAT_TOKEN'),
    ],

    'freshdesk' => [
        'url'           => env('FRESHDESK_URL'),
        'urlind'        => env('FRESHDESK_URL_IND'),
        'url2'          => env('FRESHDESK_URL2'),
        'urlx'          => env('FRESHDESK_URLX'),
        'urlcap'        => env('FRESHDESK_URL_CAP'),
        'sandbox'       => env('FRESHDESK_SANDBOX', false),
        'sandbox_url'   => env('FRESHDESK_SANDBOX_URL'),
        'token'         => env('FRESHDESK_TOKEN'),
        'tokenind'      => env('FRESHDESK_TOKEN_IND'),
        'token2'        => env('FRESHDESK_TOKEN2'),
        'tokenx'        => env('FRESHDESK_TOKENX'),
        'tokencap'      => env('FRESHDESK_TOKEN_CAP'),
        'sandbox_token' => env('FRESHDESK_SANDBOX_TOKEN'),
        'mock'          => env('FRESHDESK_MOCK', false),

        'instance_subcategory_group_ids' => [
            'rzpsol' => [
                'Technical support'     => env('FRESHDESK_RZPSOL_TECHNICAL_SUPPORT_GROUP_ID', ''),
                'Integrations'          => env('FRESHDESK_RZPSOL_INTEGRATIONS_GROUP_ID', ''),
            ],
            'rzpind' => [
                'Technical support'     => env('FRESHDESK_RZPIND_TECHNICAL_SUPPORT_GROUP_ID', ''),
                'Integrations'          => env('FRESHDESK_RZPIND_INTEGRATIONS_GROUP_ID', ''),
            ],
            'rzpcap' => [
                '*'    => env('FRESHDESK_RZPCAP_NEW_TICKET_GROUP_ID', ''),
            ],
        ],

        'instance_agent_id' => [
            'rzp'       =>  env('FRESHDESK_RZP_AGENT_ID'),
            'rzpind'    =>  env('FRESHDESK_RZP_IND_AGENT_ID'),
        ],

        'activation'  => [
            'rzp'   =>   [
                'agentId'          =>  env('WORKFLOW_ACTIVATION_RZP_AGENT_ID'),
                'groupId'          =>  env('WORKFLOW_ACTIVATION_RZP_GROUP_ID'),
                'groupIdGrievance' =>  env('FRESHDESK_CUSTOMER_GRIEVANCE_GROUP_ID_RZP'),
            ],
            'rzpind' => [
                'agentId'          =>  env('WORKFLOW_ACTIVATION_RZP_IND_AGENT_ID'),
                'groupId'          =>  env('WORKFLOW_ACTIVATION_RZP_IND_GROUP_ID'),
                'groupIdGrievance' =>  env('FRESHDESK_CUSTOMER_GRIEVANCE_GROUP_ID_RZP_IND'),
            ],
        ],

        'nodal'  => [
            'rzpind'   =>   [
                'groupIdGrievance' =>  env('FRESHDESK_CUSTOMER_NODAL_GRIEVANCE_GROUP_ID'),
            ],
        ],

        'assistant_nodal'  => [
            'rzpind'   =>   [
                'groupIdGrievance' =>  env('FRESHDESK_CUSTOMER_ASSISTANT_NODAL_GRIEVANCE_GROUP_ID'),
            ],
        ],

        'customer' => [
            'dispute' => [
                'automation_agent_id'       => env('FRESHDESK_AUTOMATION_AGENT_ID'),
                'automation_group_id'       => env('FRESHDESK_AUTOMATION_GROUP_ID'),
                'customer_support_group_id' => env('FRESHDESK_CUSTOMER_SUPPORT_GROUP_ID'),
                'rzpind' => [
                    'automation_agent_id'       => env('FRESHDESK_IND_AUTOMATION_AGENT_ID'),
                    'automation_group_id'       => env('FRESHDESK_IND_AUTOMATION_GROUP_ID'),
                    'customer_support_group_id' => env('FRESHDESK_IND_CUSTOMER_SUPPORT_GROUP_ID'),
                ],
            ],
        ],

        'group_ids' => [
            'merchant_risk'         => env('FRESHDESK_GROUP_MERCHANT_RISK_ID'),
            'plugin_merchant'       => env('FRESHDESK_GROUP_PLUGIN_MERCHANT'),
            'rzpind' => [
                'merchant_risk' => env('FRESHDESK_IND_GROUP_MERCHANT_RISK_ID'),
                'foh'           => env('FRESHDESK_IND_GROUP_FOH_NOTIFICATION_ID'),
                'byers_risk'    => env('FRESHDESK_IND_GROUP_BUYERS_RISK_ID'),
                'chargeback'    => env('FRESHDESK_IND_GROUP_CHARGEBACK_ID'),
                'merchant_risk_transaction' => env('FRESHDESK_IND_GROUP_MERCHANT_RISK_TRANSACTION_ID'),
                'debit_note'    => env('FRESHDESK_IND_GROUP_DEBIT_NOTE_ID')
            ],
            'cybercrime_helpdesk'   => [
                'acknowledgement'   => env('FRESHDESK_GROUP_MERCHANT_CYBERCRIME_HELPDESK_ID'),
                'reply_to_lea'      => env('FRESHDESK_GROUP_MERCHANT_CYBERCRIME_HELPDESK_ID'),
            ]
        ],

        'email_config_ids' => [
            'risk_notification' => env('FRESHDESK_EMAIL_CONFIG_RISK_NOTIFICATION_ID'),
            'rzpind' => [
                'risk_notification'       => env('FRESHDESK_IND_EMAIL_CONFIG_RISK_NOTIFICATION_ID'),
                'foh_notification'        => env('FRESHDESK_IND_EMAIL_CONFIG_FOH_NOTIFICATION_ID'),
                'debit_note_notification' => env('FRESHDESK_IND_EMAIL_CONFIG_DEBIT_NOTE_NOTIFICATION_ID')
            ],
            'cybercrime_helpdesk'   => [
                'acknowledgement'   => env('FRESHDESK_CYBERCRIME_HELPDESK_EMAIL_CONFIG_RISK_NOTIFICATION_ID'),
                'reply_to_lea'      => env('FRESHDESK_CYBERCRIME_HELPDESK_EMAIL_CONFIG_RISK_NOTIFICATION_ID'),
                'notify_merchant'   => env('FRESHDESK_CYBERCRIME_HELPDESK_EMAIL_CONFIG_RISK_NOTIFICATION_ID')
            ]
        ],
    ],

    'freshdesk_webhook' => [
        'secret'  => env('FRESHDESK_WEBHOOK_SECRET'),
    ],

    'pgos' => [
        'secret'       => env('PGOS_SERVICE_API_SECRET'),
    ],
    'ezetap-api' => [
        'key'       => 'ezetap-api',
        'secret'    =>  env('EZETAP_API_SECRET'),
    ],
    'friend_buy' => [
        // Api url for merchant risks service.
        'url'                => env('FRIEND_BUY_URL'),
        'mock'               => env('FRIEND_BUY_SERVICE_MOCK', false),
        'response'           => env('FRIEND_BUY_SERVICE_MOCK_RESPONSE', 'success'),
        'request_timeout'    => env('FRIEND_BUY_REQUEST_TIMEOUT', 4000),
        'connection_timeout' => env('FRIEND_BUY_CONNECTION_TIMEOUT', 2000),
        'auth'               => [
            'key'           => env('FRIEND_BUY_API_AUTH_CLIENT_KEY'),
            'secret'        => env('FRIEND_BUY_API_AUTH_SECRET_KEY'),
        ],
        'webhook'=> [
            'hash_key'    => env('FRIEND_BUY_WEBHOOK_HASH_SECRET')
        ]
    ],
    'friend_buy_webhook' => [
        'secret'  => env('FRIEND_BUY_WEBHOOK_BASIC_AUTH_PASSWORD'),
    ],
    'mailmodo' => [
        // Api url for merchant risks service.
        'url'                       => env('MAILMODO_URL'),
        'mock'                      => env('MAILMODO_SERVICE_MOCK', false),
        'response'                  => env('MAILMODO_SERVICE_MOCK_RESPONSE', 'success'),
        'request_timeout'           => env('MAILMODO_REQUEST_TIMEOUT', 4000),
        'connection_timeout'        => env('MAILMODO_CONNECTION_TIMEOUT', 2000),
        'trigger_email_endpoint'    => env('MAILMODO_EMAIL_TRIGGER_API_ENDPOINT'),
        'l1_campaign_id'            => env('MAILMODO_L1_CAMPAIGN_ID'),
        'auth'                      => [
            'key'           => env('MAILMODO_API_AUTH_CLIENT_KEY'),
            'secret'        => env('MAILMODO_API_AUTH_SECRET_KEY'),
        ],
    ],
    'yellowmessenger'   => [
        'secret'  => env('YELLOWMESSENGER_SECRET'),
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
        ],
        'phonepe' => [
            'url'       => env('PHONEPE_API_URL'),
            'secret'    => env('PHONEPE_API_SECRET'),
            'mock'      => env('PHONEPE_DOWNTIME_MOCK', false)
        ],
        'slack' => [
            'url'           => env('SLACK_POSTMESSAGE_URL'),
            'bearer_token'  => env('SLACK_BEARER_TOKEN'),
            'mock'          => env('DOWNTIME_SLACK_MOCK', true)
        ]
    ],

    'settlements_service' => [
        'url'               => [
          'live'    => env('SETTLEMENTS_LIVE_URL'),
          'test'    => env('SETTLEMENTS_TEST_URL'),
        ],
        'dashboard' => [
            'mock' => env('SETTLEMENTS_DASHBOARD_MOCK', false),
            'live' => [
                'key'       => env('SETTLEMENTS_DASHBOARD_LIVE_KEY'),
                'secret'    => env('SETTLEMENTS_DASHBOARD_LIVE_SECRET'),
            ],
            'test' => [
                'key'       => env('SETTLEMENTS_DASHBOARD_TEST_KEY'),
                'secret'    => env('SETTLEMENTS_DASHBOARD_TEST_SECRET'),
            ],
        ],
        'reminder' => [
            'live' => [
                'key'       => env('SETTLEMENTS_REMINDER_LIVE_KEY'),
                'secret'    => env('SETTLEMENTS_REMINDER_LIVE_SECRET'),
            ],
            'test' => [
                'key'       => env('SETTLEMENTS_REMINDER_TEST_KEY'),
                'secret'    => env('SETTLEMENTS_REMINDER_TEST_SECRET'),
            ],
        ],
        'api' => [
            'mock' => env('SETTLEMENTS_API_MOCK', false),
            'live' => [
                'key'       => env('SETTLEMENTS_API_LIVE_KEY'),
                'secret'    => env('SETTLEMENTS_API_LIVE_SECRET'),
            ],
            'test' => [
                'key'       => env('SETTLEMENTS_API_TEST_KEY'),
                'secret'    => env('SETTLEMENTS_API_TEST_SECRET'),
            ],
        ],
        'payout' =>[
            'live' => [
                'key'       => env('SETTLEMENTS_PAYOUT_LIVE_KEY'),
                'secret'    => env('SETTLEMENTS_PAYOUT_LIVE_SECRET'),
            ],
            'test' => [
                'key'       => env('SETTLEMENTS_PAYOUT_TEST_KEY'),
                'secret'    => env('SETTLEMENTS_PAYOUT_TEST_SECRET'),
            ],
        ],
        'merchant_dashboard' => [
            'mock' => env('SETTLEMENTS_MERCHANT_DASHBOARD_MOCK', false),
            'live' => [
                'key'       => env('SETTLEMENTS_MERCHANT_DASHBOARD_LIVE_KEY'),
                'secret'    => env('SETTLEMENTS_MERCHANT_DASHBOARD_LIVE_SECRET'),
            ],
            'test' => [
                'key'       => env('SETTLEMENTS_MERCHANT_DASHBOARD_TEST_KEY'),
                'secret'    => env('SETTLEMENTS_MERCHANT_DASHBOARD_TEST_SECRET'),
            ],
        ],
        'secret'            => env('SETTLEMENTS_SERVICE_SECRET'),
    ],

    'einvoice' => [
        'url'               => [
            'live'    =>   env('GSP_LIVE_URL'),
            'test'    =>   env('GSP_TEST_URL'),
        ],
        'access_token' => [
            'rspl'     =>   [
                'live' => [
                    'username'              => env('GSP_LIVE_ACCESS_TOKEN_USERNAME'),
                    'password'              => env('GSP_LIVE_ACCESS_TOKEN_PASSWORD'),
                    'client_id'             => env('GSP_LIVE_ACCESS_TOKEN_CLIENT_ID'),
                    'client_secret'         => env('GSP_LIVE_ACCESS_TOKEN_CLIENT_SECRET'),
                    'grant_type'            => env('GSP_LIVE_ACCESS_TOKEN_GRANT_TYPE'),
                    'static_access_token'   => env('GSP_LIVE_STATIC_ACCESS_TOKEN'),
                ],
                'test' => [
                    'username'              => env('GSP_TEST_ACCESS_TOKEN_USERNAME'),
                    'password'              => env('GSP_TEST_ACCESS_TOKEN_PASSWORD'),
                    'client_id'             => env('GSP_TEST_ACCESS_TOKEN_CLIENT_ID'),
                    'client_secret'         => env('GSP_TEST_ACCESS_TOKEN_CLIENT_SECRET'),
                    'grant_type'            => env('GSP_TEST_ACCESS_TOKEN_GRANT_TYPE'),
                    'static_access_token'   => env('GSP_TEST_STATIC_ACCESS_TOKEN'),
                ],
            ],
            'rzpl'     =>   [
                'live' => [
                    'username'              => env('GSP_LIVE_ACCESS_TOKEN_USERNAME'),
                    'password'              => env('GSP_LIVE_ACCESS_TOKEN_PASSWORD'),
                    'client_id'             => env('GSP_LIVE_ACCESS_TOKEN_CLIENT_ID'),
                    'client_secret'         => env('GSP_LIVE_ACCESS_TOKEN_CLIENT_SECRET'),
                    'grant_type'            => env('GSP_LIVE_ACCESS_TOKEN_GRANT_TYPE'),
                    'static_access_token'   => env('GSP_LIVE_STATIC_ACCESS_TOKEN_X'),
                ],
                'test' => [
                    'username'              => env('GSP_TEST_ACCESS_TOKEN_USERNAME'),
                    'password'              => env('GSP_TEST_ACCESS_TOKEN_PASSWORD'),
                    'client_id'             => env('GSP_TEST_ACCESS_TOKEN_CLIENT_ID'),
                    'client_secret'         => env('GSP_TEST_ACCESS_TOKEN_CLIENT_SECRET'),
                    'grant_type'            => env('GSP_TEST_ACCESS_TOKEN_GRANT_TYPE'),
                    'static_access_token'   => env('GSP_TEST_STATIC_ACCESS_TOKEN_X'),
                ],
            ],
        ],
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
        'mock'           => env('PINCODE_MOCK', false),
        'url'            => env('PINCODE_BASE_URL'),
        'api_key'        => env('PINCODE_SEARCH_API_KEY'),
        'google_api_key' => env('MAGIC_CHECKOUT_MAPS_API_KEY'),
    ],

    'banking_account' => [
        'apiKey' => env('GOOGLE_MAP_API_KEY'),
        'mock' => env('BANKING_ACCOUNT_GOOGLE_MAPS_MOCK', false)
    ],

    'shield' => [
        'mock'     => env('SHIELD_MOCK', false),
        'url'      => env('SHIELD_BASE_URL'),
        'url_international'      => env('SHIELD_BASE_URL_INTERNATIONAL'),
        'mock_url' => env('SHIELD_BASE_MOCK_URL'),
        'auth' => [
            'username' => 'api',
            'password' => env('SHIELD_SECRET'),
        ],
        'slack' => [
            'cc_user_ids'         => env('SHIELD_SLACK_CC_USER_IDS'),
            'eligible_rule_codes' => env('SHIELD_SLACK_NOTIFICATION_RULE_CODES'),
            'url'                 => env('SHIELD_SLACK_POSTMESSAGE_URL'),
            'bearer_token'        => env('SHIELD_SLACK_BEARER_TOKEN'),
            'mock'                => env('SHIELD_SLACK_MOCK'),
        ],
    ],

    'razorx' => [
        'mock'                 => env('RAZORX_MOCK', false),
        'url'                  => env('RAZORX_URL'),
        'username'             => 'rzp_api',
        'secret'               => env('RAZORX_SECRET'),
        'request_timeout'      => env('RAZORX_REQUEST_TIMEOUT', 0.1),
        'request_timeout_bulk' => env('RAZORX_REQUEST_TIMEOUT_BULK', 1),
    ],

    'splitz' => [
        'mock'            => env('SPLITZ_MOCK', false),
        'url'             => env('SPLITZ_URL'),
        'username'        => 'api',
        'secret'          => env('SPLITZ_SECRET'),
        'request_timeout' => env('SPLITZ_REQUEST_TIMEOUT', 0.1),
        'evaluate_request_timeout' => env('SPLITZ_EVALUATE_REQUEST_TIMEOUT', 0.1),
        'bulk_evaluate_request_timeout' => env('SPLITZ_BULK_EVALUATE_REQUEST_TIMEOUT', 0.5),
    ],

    'growth' => [
        'mock'            => env('GROWTH_MOCK', false),
        'url'             => env('GROWTH_URL'),
        'username'        => 'api',
        'secret'          => env('GROWTH_SECRET'),
        'request_timeout' => env('GROWTH_REQUEST_TIMEOUT', 0.1),
        'skip_jwt_passport'=> env('SKIP_PASSPORT_AUTH', false),
    ],

    'growth_internal' => [
        'secret'   => env('GROWTH_INTERNAL_SECRET'),
    ],

    'partnerships'   => [
        'mock'              => env('PARTNERSHIPS_MOCK', false),
        'url'               => [
            'live'          => env('PARTNERSHIPS_LIVE_URL'),
            'test'          => env('PARTNERSHIPS_TEST_URL')
        ],
        'username'          => 'api',
        'secret'            => env('PARTNERSHIPS_API_SECRET'),
        'request_timeout'   => env('PARTNERSHIPS_REQUEST_TIMEOUT', 0.1),
        'skip_jwt_passport' => env('PARTNERSHIPS_SKIP_PASSPORT_AUTH', true),
    ],

    'user_2fa' => [
        'max_incorrect_tries' => env('USER_2FA_MAX_INCORRECT_TRIES', 9),
    ],

    'admin_2fa' => [
        'max_incorrect_tries' => env('ADMIN_2FA_MAX_INCORRECT_TRIES', 9),
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
        'new_url'  => env('BEAM_NEW_URL'),
    ],

    'chota_beam' => [
        'url'  => env('CHOTABEAM_URL'),
        'mock' => env('CHOTABEAM_MOCK', false),
        'bucket_name' => env('CHOTABEAM_BUCKET_NAME'),
    ],

    'subscriptions' => [
        'url'      => env('APP_SUBSCRIPTIONS_URL'),
        'username' => 'rzp',
        'secret'   => env('APP_SUBSCRIPTIONS_SECRET'),
        'timeout'  => env('SUBSCRIPTION_SERVICE_TIMEOUT', 10),
        'queue_sync'  => env('SUBSCRIPTION_SERVICE_QUEUE_SYNC', false),
    ],

    'mandate_hq' => [
        'secret'         => env('APP_MANDATE_HQ_SECRET'),
        'mock'           => env('APP_MANDATE_HQ_MOCK', false),
        'webhook_secret' => env('APP_MANDATE_HQ_WEBHOOK_SECRET'),

        'url'            => env('APP_MANDATE_HQ_URL'),
        'username'       => env('APP_MANDATE_HQ_LIVE_USERNAME'),
        'password'       => env('APP_MANDATE_HQ_LIVE_PASSWORD'),

        'test_mode_url'      => env('APP_MANDATE_HQ_TEST_URL'),
        'test_mode_username' => env('APP_MANDATE_HQ_TEST_USERNAME'),
        'test_mode_password' => env('APP_MANDATE_HQ_TEST_PASSWORD'),
    ],

    'loan_origination_system' => [
        'mock'          => env('APP_LOAN_ORIGINATION_SYSTEM_MOCK', false),
        'url'           => env('APP_LOAN_ORIGINATION_SYSTEM_URL'),
        'username'      => 'key',
        'secret'        => env('APP_LOAN_ORIGINATION_SYSTEM_SECRET'),
        'timeout'       => env('APP_LOAN_ORIGINATION_SYSTEM_TIMEOUT', 60),
    ],

    'line_of_credit' => [
        'url'           => env('APP_LINE_OF_CREDIT_URL'),
        'username'      => 'key',
        'secret'        => env('APP_LINE_OF_CREDIT_SECRET'),
        'timeout'       => env('APP_LINE_OF_CREDIT_TIMEOUT', 60),
    ],

    'capital_marketplace' => [
        'url'           => env('APP_MARKETPLACE_URL'),
        'username'      => 'api',
        'secret'        => env('APP_MARKETPLACE_SECRET'),
        'timeout'       => env('APP_MARKETPLACE_TIMEOUT', 60),
    ],

    'capital_scorecard' => [
        'url'           => env('APP_SCORECARD_URL'),
        'username'      => 'api',
        'secret'        => env('APP_SCORECARD_SECRET'),
        'timeout'       => env('APP_SCORECARD_TIMEOUT', 90),
    ],

    'capital_lender' => [
        'url'           => env('APP_LENDER_URL'),
        'username'      => 'api',
        'secret'        => env('APP_LENDER_SECRET'),
        'timeout'       => env('APP_LENDER_TIMEOUT', 90),
    ],

    'capital_es' => [
        'url'           => env('APP_ES_URL'),
        'username'      => env('APP_ES_USERNAME'),
        'secret'        => env('APP_ES_SECRET'),
        'timeout'       => env('APP_ES_TIMEOUT', 90),
    ],

    'wallet' => [
        'timeout'     => env('APP_WALLET_TIMEOUT', 60),
        'url'         => [
            'live'    =>   env('APP_WALLET_LIVE_URL'),
            'test'    =>   env('APP_WALLET_TEST_URL'),
        ],
        'api' => [
            'live' => [
                'username'  => 'api',
                'secret'    => env('WALLET_API_LIVE_SECRET'),
            ],
            'test' => [
                'username'  => 'api',
                'secret'    => env('WALLET_API_TEST_SECRET'),
            ],
        ],
    ],

    'capital_cards' => [
        'url'           => env('APP_CAPITAL_CARDS_URL'),
        'username'      => 'api',
        'secret'        => env('APP_CAPITAL_CARDS_SECRET', 'api'),
        'timeout'       => env('APP_CAPITAL_CARDS_TIMEOUT', 60),
    ],

    'capital_collections' => [
        'url'           => env('APP_CAPITAL_COLLECTIONS_URL'),
        'username'      => 'api',
        'secret'        => env('APP_CAPITAL_COLLECTIONS_SECRET', 'api'),
        'timeout'       => env('APP_CAPITAL_COLLECTIONS_TIMEOUT', 60),
        'webhook_secret'=> env('APP_CAPITAL_COLLECTIONS_WEBHOOK_SECRET'),
    ],

    'payment_links' => [
        'mock'          => env('MOCK_PAYMENT_LINK_SERVICE', false),
        'url'           => env('APP_PAYMENT_LINKS_URL'),
        'username'      => 'api',
        'secret'        => env('APP_PAYMENT_LINKS_SECRET'),
        'timeout'       => env('APP_PAYMENT_LINKS_TIMEOUT_SECS'),
        'pl_urls'       => [
            'verify_order'          => 'v1/payment_links_verify_payment',
            'evict_merchant_cache'  => 'v1/evict_merchant_cache',
        ],
    ],

    'no_code_apps' => [
        'mock'          => env('MOCK_NOCODE_APP_SERVICE', true),
        'url'           => env('NOCODE_APP_SERVICE_URL'),
        'username'      => env('NOCODE_APP_SERVICE_USERNAME'),
        'secret'        => env('NOCODE_APP_SERVICE_SECRET'),
        'timeout'       => env('NOCODE_APP_SERVICE_TIMEOUT_SECS'),
        'nca_urls'      => [
            'example'           => 'example',
            'payment_process'   => 'payments/%s/process',
        ]
    ],
    'smart_collect' => [
        'mock'          => env('SMART_COLLECT_MOCK', false),
        'url'           => env('SMART_COLLECT_URL'),
        'username'      => 'api',
        'password'      => env('SMART_COLLECT_PASSWORD'),
        'secret'        => env('SMART_COLLECT_SECRET'),
        'timeout'       => env('SMART_COLLECT_TIMEOUT_SECS'),
    ],

    'myoperator' => [
        'mock'              => env('MYOPERATOR_MOCK'),
        'api_token'         => env('MYOPERATOR_API_TOKEN'),
        'secret_token'      => env('MYOPERATOR_SECRET_TOKEN'),
        'secret'            => env('MYOPERATOR_INTERNAL_APP_SECRET'),
        'x_api_token'       => env('X_MYOPERATOR_API_TOKEN'),
        'x_api_key'         => env('MYOPERATOR_X_API_KEY'),
        'x_public_ivr_id'   => env('X_MYOPERATOR_PUBLIC_IVR_ID'),
        'x_company_id'      => env('X_MYOPERATOR_COMPANY_ID')
    ],

    'vendor_payments' => [
        'url' => env('VENDOR_PAYMENT_URL'),
        // the secret used by the VP to call apis under internal auth
        // this same secret is used as the password to call APIs on the micro-service
        'secret' => env('VENDOR_PAYMENT_INTERNAL_APP_SECRET'),
        'timeout' => env('VENDOR_PAYMENT_TIMEOUT_SECS', 60),
        'tax_payment_lite_fe_endpoint' => env('TAX_PAYMENT_LITE_FE_ENDPOINT', ''),
        'vendor_portal_merchant_id' => env('VENDOR_PORTAL_MERCHANT_ID', ''),
        'public_approve_reject_url' => env('VENDOR_PAYMENT_PUBLIC_APPROVE_REJECT_URL', '')
    ],

    'accounts_receivable' => [
        'url' => env('ACCOUNTS_RECEIVABLE_HOST_URL'),
        // the secret used by the accounts receviable to call apis under internal auth
        // this same secret is used as the password to call APIs on the micro-service
        'secret' => env('ACCOUNTS_RECEIVABLE_PASSWORD'),
        'timeout' => env('ACCOUNTS_RECEIVABLE_TIMEOUT_SECS', 60),
    ],

    'business_reporting' => [
        'url' => env('BUSINESS_REPORTING_HOST_URL'),
        // the secret used by the accounts receviable to call apis under internal auth
        // this same secret is used as the password to call APIs on the micro-service
        'secret' => env('BUSINESS_REPORTING_PASSWORD'),
        'timeout' => env('BUSINESS_REPORTING_TIMEOUT_SECS', 60),
    ],

    'accounting_integrations' => [
        'url' => env('ACCOUNTING_INTEGRATIONS_HOST_URL'),
        // the secret used by the accounts receviable to call apis under internal auth
        // this same secret is used as the password to call APIs on the micro-service
        'secret' => env('ACCOUNTING_INTEGRATIONS_PASSWORD'),
        'timeout' => env('ACCOUNTING_INTEGRATIONS_TIMEOUT_SECS', 60),
    ],

    'banking_service_url' => env('BANKING_SERVICE_URL', 'https://x.razorpay.com'),
    'bank_lms_banking_service_url' => env('BANK_LMS_BANKING_SERVICE_URL', 'https://partner-lms.razorpay.com'),

    'payout_links' => [
        'url'                    => env('APP_PAYOUT_LINKS_URL', 'http://localhost:8000'),
        'secret'                 => env('APP_PAYOUT_LINKS_INTERNAL_SECRET'),
        'micro_service_endpoint' => env('PAYOUT_LINKS_MICRO_SERVICE_URL', 'http://localhost:8000'),
        'app_demo_payout_link_fe_endpoint' => env('APP_DEMO_PAYOUT_LINK_FE_ENDPOINT', ''),
        'timeout'                => env('PAYOUT_LINKS_URL_TIMEOUT_SECS', 25)
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

    'relay' => [
        'mock'   => env('RELAY_MOCK', false),
        'secret' => env('APP_RELAY_SECRET'),
        'test'   => [
            'url'                   => env('RELAY_URL_TEST'),
            'key'                   => env('RELAY_KEY_TEST'),
            'secret'                => env('RELAY_SECRET_TEST'),
        ],
        'live'   => [
            'url'                   => env('RELAY_URL_LIVE'),
            'key'                   => env('RELAY_KEY_LIVE'),
            'secret'                => env('RELAY_SECRET_LIVE'),
        ],
    ],

    'batch' => [
        'secret'            => env('BATCH_API_SECRET'),
        'mock'              => env('BATCH_MOCK',false),
        'url'               => env('BATCH_SERVICE_URL'),
        'username'          => env('BATCH_USERNAME'),
        'password'          => env('BATCH_PASSWORD'),
        'aes_key'           => env('BATCH_AES_KEY'),
    ],

    'stakeholders' => [
        'aes_key'   => env('STAKEHOLDERS_AES_KEY'),
    ],

    'smart_routing' => [
        'url'       => env('SMART_ROUTING_URL'),
        'mock'      => env('SMART_ROUTING_MOCK',false),
        'secret'    => env('SMART_ROUTING_SECRET'),
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

    'downtime_service' => [
        'secret'    => env('DOWNTIME_SERVICE_SECRET'),
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

    'spinnaker'  => [
        'secret'        => env('SPINNAKER_SECRET'),
    ],

    'card_payment_service' => [
        'mock'      => env('CARD_PAYMENT_SERVICE_MOCK', false),
        'secret'    => env('CARD_PAYMENT_SERVICE_SECRET'),
        'username'  => env('CARD_PAYMENT_SERVICE_KEY'),
        'password'  => env('CARD_PAYMENT_SERVICE_SECRET'),
        'url'       => [
            'live' => env('CARD_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('CARD_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'nbplus_payment_service' => [
        'mock'      => env('NBPLUS_PAYMENT_SERVICE_MOCK', false),
        'secret'    => env('NBPLUS_PAYMENT_SERVICE_SECRET'),
        'username'  => env('NBPLUS_PAYMENT_SERVICE_KEY'),
        'password'  => env('NBPLUS_PAYMENT_SERVICE_SECRET'),
        'url'       => [
            'live' => env('NBPLUS_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('NBPLUS_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'dcs' => [
        'mock'      => env('DCS_MOCK', false),
        'live'       => [
            "url"       => env('DCS_LIVE_URL'),
            'username'  => env('DCS_AUTH_API_USERNAME_LIVE'),
            'password'  => env('DCS_AUTH_API_PASSWORD_LIVE'),
        ],
        'test'       => [
            "url"       => env('DCS_TEST_URL'),
            'username'  => env('DCS_AUTH_API_USERNAME_TEST'),
            'password'  => env('DCS_AUTH_API_PASSWORD_TEST'),
        ],
    ],

    'dcs_service_integrations' => [
        'mock'      => env('DCS_EXTERNAL_MOCK', false),
        'checkout-affordability-api' => [
            'live'       => [
                'url'       => env('CHECKOUT_AFFORDABILITY_API_LIVE_URL'),
                'username'  => env('CHECKOUT_AFFORDABILITY_API_USERNAME_LIVE'),
                'password'  => env('CHECKOUT_AFFORDABILITY_API_PASSWORD_LIVE'),
            ],
            'test'       => [
                "url"       => env('CHECKOUT_AFFORDABILITY_API_TEST_URL'),
                'username'  => env('CHECKOUT_AFFORDABILITY_API_USERNAME_TEST'),
                'password'  => env('CHECKOUT_AFFORDABILITY_API_PASSWORD_TEST'),
            ],
        ],
        'pg-router' => [
            'live'       => [
                'url'       => env('PG_ROUTER_URL'),
                'username'  => env('PG_ROUTER_KEY'),
                'password'  => env('PG_ROUTER_SECRET'),
            ],
            'test'       => [
                "url"       => env('PG_ROUTER_URL'),
                'username'  => env('PG_ROUTER_KEY'),
                'password'  => env('PG_ROUTER_SECRET'),
            ],
        ],
        'capital-los' => [
            'live'=>[
                'url'      => env('APP_LOAN_ORIGINATION_SYSTEM_URL'),
                'username' => 'key',
                'password' => env('APP_LOAN_ORIGINATION_SYSTEM_SECRET'),
            ],
            'test'=>[
                'url'      => env('APP_LOAN_ORIGINATION_SYSTEM_URL'),
                'username' => 'key',
                'password' => env('APP_LOAN_ORIGINATION_SYSTEM_SECRET'),
            ],
        ],
        'capital-loc' => [
            'live'=>[
                'url'      => env('APP_LINE_OF_CREDIT_URL'),
                'username' => 'key',
                'password' => env('APP_LINE_OF_CREDIT_SECRET'),
            ],
            'test'=>[
                'url'      => env('APP_LINE_OF_CREDIT_URL'),
                'username' => 'key',
                'password' => env('APP_LINE_OF_CREDIT_SECRET'),
                ],
            ],
        'capital-es' => [
            'live'=>[
                'url'      => env('APP_ES_DCS_URL'),
                'username' => env('APP_ES_USERNAME'),
                'password' => env('APP_ES_SECRET'),
            ],
            'test'=>[
                'url'      => env('APP_ES_DCS_URL'),
                'username' => env('APP_ES_USERNAME'),
                'password' => env('APP_ES_SECRET'),
            ],
        ],
        'scrooge' => [
            'live'=>[
                'url'      => env('SCROOGE_DCS_BASE_URL'),
                'username' => env('SCROOGE_KEY'),
                'password' => env('SCROOGE_SECRET'),
            ],
            'test'=>[
                'url'      => env('SCROOGE_DCS_BASE_URL'),
                'username' => env('SCROOGE_KEY'),
                'password' => env('SCROOGE_SECRET'),
            ],
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
        // to add internalApp Auth which uses secret field to determine whether It is Internal Auth
        'secret'        => env('SALESFORCE_SECRET'),
    ],

    'salesforce_converge' => [
        'mock'          => env('SALESFORCE_CONVERGE_MOCK', false),
        'url'           => env('SALESFORCE_CONVERGE_URL'),
        'username'      => env('SALESFORCE_CONVERGE_USERNAME'),
        'password'      => env('SALESFORCE_CONVERGE_PASSWORD'),
        'client_id'     => env('SALESFORCE_CONVERGE_CLIENT_ID'),
        'client_secret' => env('SALESFORCE_CONVERGE_CLIENT_SECRET'),
        // to add internalApp Auth which uses secret field to determine whether It is Internal Auth
        'secret'        => env('SALESFORCE_CONVERGE_SECRET'),
    ],

    'terminals_service' => [
        'mock'          => env('TERMINALS_SERVICE_MOCK', false),
        'secret'        => env('TERMINALS_SERVICE_API_SECRET'),
        'live'          => [
            'password'      => env('TERMINALS_SERVICE_LIVE_PASSWORD'),
            'url'           => env('TERMINALS_SERVICE_LIVE_URL'),
        ],
        'test'          => [
            'password'      => env('TERMINALS_SERVICE_TEST_PASSWORD'),
            'url'           => env('TERMINALS_SERVICE_TEST_URL'),
        ],
        'timeout'       => env('TERMINALS_SERVICE_TIMEOUT'),
        'sync'          => env('SYNC_WITH_TERMINAL_SERVICE'),
        'dashboard_timeout' => env('TERMINAL_SERVICE_ADMIN_DASHBOARD_TIMEOUT', 5),
    ],

    'financial_data_service' => [
        'url'           => env('APP_FINANCIAL_DATA_SERVICE_URL'),
        'username'      => 'api',
        'secret'        => env('APP_FINANCIAL_DATA_SERVICE_SECRET'),
        'timeout'       => env('APP_FINANCIAL_DATA_SERVICE_TIMEOUT', 60),
    ],

    'paypal' => [
        'merchant_on_boarding_completed_webhook_id' => env('PAYPAL_MERCHANT_ON_BOARDING_COMPLETED_WEBHOOK_ID'),
    ],

    'typeform' => [
        'typeform_webhook_secret'  => env('TYPEFORM_WEBHOOK_SECRET'),
        'typeform_api_key'         => env('TYPEFORM_API_KEY'),
    ],

    'workflows' => [
        'mock'                          => env('WORKFLOWS_MOCK', false),
        'url'                           => env('WORKFLOWS_URL'),
        'username'                      => env('WORKFLOWS_USERNAME'),
        'password'                      => env('WORKFLOWS_PASSWORD'),
        'secret'                        => env('WORKFLOWS_INTERNAL_APP_SECRET'),
        'spr_config_id'                 => env('WORKFLOWS_SPR_CONFIG_ID'),
        'cross_border' => [
            'invoice_verification_config_id'         => env('WORKFLOWS_CROSS_BORDER_INVOICE_VERIFICATION_CONFIG_ID'),
            'invoice_verification_dashboard_domain'  => env('WORKFLOWS_CROSS_BORDER_INVOICE_VERIFICATION_DOMAIN')
        ]
    ],

    'worker' => [
        'is_worker_pod'  => env('IS_WORKER_POD', false),
    ],

    'rzp_sftp' => [
        'rzp_sftp_secret_key'       => env('RZP_SFTP_PASSWORD'),
        'rzp_sftp_sftp_username'    => env('RZP_SETTLEMENT_SFTP_USERNAME'),
        'rzp_sftp_file_path'        => env('RZP_SETTLEMENT_SFTP_FILE_PATH'),
    ],

    'jaeger' => [
        'enabled'               => env('DISTRIBUTED_TRACING_ENABLED', false),
        'host'                  => env('JAEGER_HOSTNAME', env('NODE_NAME')),
        'port'                  => env('JAEGER_PORT', 6831),
        'app_mode'              => env('INSTANCE_TYPE', ''),
        'tag_service_version'   => env('GIT_COMMIT_HASH', ''),
        'tag_app_env'           => env('APP_ENV', '')
    ],

    'payouts_service' => [
        'url'    => env('PAYOUTS_URL'),
        'secret' => env('PAYOUTS_INTERNAL_APP_SECRET'),
        'live'   => [
            'payout_key'    => env('PAYOUTS_AUTH_API_USERNAME_LIVE'),
            'payout_secret' => env('PAYOUTS_AUTH_API_PASSWORD_LIVE')
        ],
        'test'   => [
            'payout_key'    => env('PAYOUTS_AUTH_API_USERNAME_TEST'),
            'payout_secret' => env('PAYOUTS_AUTH_API_PASSWORD_TEST')
        ]
    ],

    'api_whatsapp' => [
        'key'    => env('API_WHATSAPP_INTERNAL_APP_KEY'),
        'secret' => env('API_WHATSAPP_INTERNAL_APP_SECRET'),
    ],

    'pg_router' => [
        'mock'                => env('PG_ROUTER_MOCK', false),
        'url'                 => env('PG_ROUTER_URL'),
        'pg_router_key'       => env('PG_ROUTER_KEY'),
        'pg_router_secret'    => env('PG_ROUTER_SECRET'),
        'secret'              => env('API_PG_ROUTER_SECRET'),
    ],

    // This username & password already added in API is used by API to communicate with payments upi service
    'upi_payment_service' => [
        'mock'      => env('UPI_PAYMENT_SERVICE_MOCK', false),
        'username'  => env('UPI_PAYMENT_SERVICE_KEY'),
        'password'  => env('UPI_PAYMENT_SERVICE_SECRET'),
        'enabled'   => env('UPI_PAYMENT_SERVICE_ENABLED', false),
        'url'       => [
            'live' => env('UPI_PAYMENT_SERVICE_LIVE_URL'),
            'test' => env('UPI_PAYMENT_SERVICE_TEST_URL'),
        ],
    ],

    'payments_cross_border_service' => [
        'mock'      => env('PAYMENTS_CROSS_BORDER_SERVICE_MOCK', false),
        'username'  => env('PAYMENTS_CROSS_BORDER_SERVICE_KEY', 'api_user'),
        'password'  => env('PAYMENTS_CROSS_BORDER_SERVICE_SECRET', 'RANDOM_PXB_SECRET'),
        'url'       => [
            'live' => env('PAYMENTS_CROSS_BORDER_SERVICE_LIVE_URL'),
            'test' => env('PAYMENTS_CROSS_BORDER_SERVICE_TEST_URL'),
        ],
    ],

    'pspx'  => [
      'username'  => env('PSPX_SERVICE_API_USERNAME'),
      'password'  => env('PSPX_SERVICE_API_PASSWORD'),
      'url'       => env('PSPX_SERVICE_URL'),
      'mock'      => env('PSPX_SERVICE_MOCK', false),
    ],
    'gupshup' => [
        'secret' => env('GUPSHUP_CALLBACK_SECRET')
    ],

    'bvs' => [
        'username' => env("BVS_APP_USERNAME"),
        'secret'   => env('BVS_APP_SECRET')
    ],

    'account_service' => [
        'secret'   => env('ACCOUNT_SERVICE_SECRET')
    ],

    'merchant_risk_alerts' => [
        'maker_email' => env('MERCHANT_RISK_ALERT_WORKFLOW_MAKER_EMAIL', 'shashank@razorpay.com'),
        'secret'      => env('MERCHANT_RISK_ALERTS_SECRET')
    ],

    'cyber_crime_helpdesk' => [
        'secret'      => env('CYBER_CRIME_HELPDESK', 'cyber_crime_helpdesk_secret'),
        'maker_email' => env('CYBER_CRIME_HELPDESK_WORKFLOW_MAKER_EMAIL', 'shashank@razorpay.com'),
        'auth' => [
            'username' => 'api',
            'secret' => env('CYBER_HELPDESK_API_SECRET', 'cyber_helpdesk_api_secret@1'),
        ],
        'mock'     => env('CYBER_HELPDESK_MOCK', false),
        'base_url'      => env('CYBER_HELPDESK_BASE_URL', 'https://cyber-helpdesk.stage.razorpay.in/api')
    ],

    'disputes' => [
        'auth' => [
            'username' => 'api',
            'secret' => env('DISPUTES_API_SECRET', 'dispute_secret'),
        ],
        // secret key need to be added here to run the UTs
        'secret' =>  env('DISPUTES_API_SECRET', 'dispute_secret'),
        'mock'     => env('DISPUTES_MOCK', false),
        'base_url'      => env('DISPUTES_BASE_URL', 'https://disputes.int.stage.razorpay.in/api/')
    ],

    'sms_sync'  =>  [
        'secret'   => env('SMS_SYNC_SECRET')
    ],

    'care'  => [
        'secret'       => env('CARE_SERVICE_API_SECRET'),
        'host'         => env('CARE_SERVICE_HOST'),
        'dark-host'    => env('CARE_SERVICE_DARK_HOST'),
        'password'     => env('CARE_SERVICE_PASSWORD'),
    ],

    'cmma' => [
        'secret'       => env('CMMA_SERVICE_API_SECRET'),
    ],

    'templating'    => [
        'user'          => env('TEMPLATING_SERVICE_AUTH_KEY'),
        'password'      => env('TEMPLATING_SERVICE_AUTH_SECRET'),
        'url'           => env('TEMPLATING_SERVICE_URL'),
    ],

    'media_service' => [
        'user'          => env('MEDIA_SERVICE_AUTH_KEY'),
        'password'      => env('MEDIA_SERVICE_AUTH_SECRET'),
        'url'           => env('MEDIA_SERVICE_URL'),
    ],

    'rzp_labs'  => [
        'slack_app' =>  [
            'user'      => env('SLACK_APP_USERNAME'),
            'password'  => env('SLACK_APP_PASSWORD'),
            'url'       => env('SLACK_APP_URL'),
        ],
    ],

    'ledger' => [
        'enabled'   => env('LEDGER_ENABLED', false),
        'url'       => [
            'live' => env('LEDGER_LIVE_URL'),
            'test' => env('LEDGER_TEST_URL'),
        ],
        // Key and secret through which api will call ledger
        'ledger_key'        => env('LEDGER_KEY'),
        'ledger_secret'     => env('LEDGER_SECRET'),
        // ledger will call api using this secret
        'secret'            => env('LEDGER_API_SECRET'),
        'tidb_db_name' => [
            'live' => env('LEDGER_LIVE_TIDB_DB_NAME'),
            'test' => env('LEDGER_TEST_TIDB_DB_NAME'),
        ],
    ],

    'developer_console' => [
        'enabled'   => env('DEVELOPER_CONSOLE_ENABLED', false),
        'host'     => env('DEVELOPER_CONSOLE_HOST'),
        'username_merchant' => env('DEVELOPER_CONSOLE_USERNAME'),
        'password_merchant' => env('DEVELOPER_CONSOLE_PASSWORD'),
        'username_admin' => env('DEVELOPER_CONSOLE_ADMIN_USERNAME'),
        'password_admin' => env('DEVELOPER_CONSOLE_ADMIN_PASSWORD'),
    ],

    'banking_account_service' => [
        'url'                      => env('APP_BANKING_ACCOUNT_SERVICE_URL'),
        'secret'                   => env('APP_BANKING_ACCOUNT_SERVICE_SECRET'),
        'timeout'                  => env('APP_BANKING_ACCOUNT_SERVICE_TIMEOUT_SECS', 30),
        'mock'                     => env('MOCK_BANKING_ACCOUNT_SERVICE', false),
        'rbl_leads_sf_time_filter' => env('RBL_LEADS_SF_TIME_FILTER', 1631903400),
    ],

    'master_onboarding' => [
        'url'                      => env('APP_MASTER_ONBOARDING_SERVICE_URL'),
        'upstream_secret'          => env('APP_MASTER_ONBOARDING_SERVICE_UPSTREAM_SECRET'),
        'timeout'                  => env('APP_MASTER_ONBOARDING_SERVICE_TIMEOUT_SECS', 30),
        'mock'                     => env('MOCK_MASTER_ONBOARDING_SERVICE', false),
        'key'                      => env('APP_MASTER_ONBOARDING_SERVICE_UPSTREAM_KEY'),
        'secret'                   => env('APP_MASTER_ONBOARDING_SERVICE_DOWNSTREAM_SECRET'),
    ],

    'acs' => [
        'sync_enabled' => env('ACS_SYNC_ENABLED', false),
        'credcase_sync_enabled' => env('CREDCASE_CONSUMER_SYNC_ENABLED', false),
        'verbose_log' => env('ACS_VERBOSE_LOG_ENABLED', false),
        'read_traffic_metric_enabled' => env('ASV_READ_TRAFFIC_METRIC_ENABLED', false),
        'write_traffic_metric_enabled' => env('ASV_WRITE_TRAFFIC_METRIC_ENABLED', false),
        'splitz_experiment_id' =>env('ASV_SPLITZ_EXPERIMENT_ID', ''),
        'mock' => env('ASV_MOCK', false),
        'host' => env('ASV_HOST', 'https://acs-web.razorpay.com'),
        'user' => env('ASV_USERNAME'),
        'password' => env('ASV_PASSWORD'),
        'asv_full_data_sync_splitz_experiment_id' => env('ASV_FULL_DATA_SYNC_SPLITZ_EXPERIMENT_ID', ''),
        'asv_http_client_timeout' => env('ASV_HTTP_CLIENT_TIMEOUT', 5),
        'sync_deviation_route_http_timeout_sec' => env('ASV_SYNC_DEVIATION_ROUTE_HTTP_TIMEOUT_SEC', 4),
        'document_delete_route_http_timeout_sec' => env('ASV_DOCUMENT_DELETE_ROUTE_HTTP_TIMEOUT_SEC',2),
        'account_contact_delete_route_http_timeout_sec' => env('ASV_ACCOUNT_CONTACT_DELETE_ROUTE_HTTP_TIMEOUT_SEC', 2),
        'asv_save_api_route_http_timeout_sec' => env('ASV_SAVE_API_ROUTE_HTTP_TIMEOUT_SEC', 2),
        'asv_fetch_route_http_timeout_sec' => env('ASV_MERCHANT_FETCH_ROUTE_HTTP_TIMEOUT_SEC', 2),
        'asv_data_sync_events_topic' => env('ASV_DATA_SYNC_EVENTS_TOPIC', 'prod-asv-data-sync-events')
    ],

    'asv_v2' => [
        'grpc_host' =>  env('ASV_V2_GRPC_HOST', 'asv-grpc.razorpay.com'),
        'username' => env('ASV_V2_USERNAME', ''),
        'password' => env('ASV_V2_PASSWORD', ''),
        'grpc_timeout' => env('ASV_V2_GRPC_CLIENT_TIMEOUT', 100000),

        // splitz experiment ids
        'splitz_experiment_website_read_merchantid' => env('ASV_SPLITZ_EXPERIMENT_WEBSITE_READ_MERCHANTID', ''),
        'splitz_experiment_website_read_find' => env('ASV_SPLITZ_EXPERIMENT_WEBSITE_READ_FIND', ''),
        'splitz_experiment_merchant_email_read_by_merchant_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_EMAIL_READ_BY_MERCHANT_ID', ''),
        'splitz_experiment_merchant_email_read_by_type_and_merchant_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_EMAIL_READ_BY_TYPE_AND_MERCHANT_ID', ''),
        'splitz_experiment_merchant_email_read_by_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_EMAIL_READ_BY_ID', ''),
        'splitz_experiment_merchant_business_detail_read_by_merchant_id' => env('ASV_SPLITZ_EXPERIMENT_BUSINESS_DETAIL_READ_BY_MERCHANT_ID', ''),
        'splitz_experiment_merchant_business_detail_read_by_id' => env('ASV_SPLITZ_EXPERIMENT_BUSINESS_DETAIL_READ_BY_ID', ''),
        'splitz_experiment_merchant_document_read_by_type_and_merchant_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_DOCUMENT_READ_BY_TYPE_AND_MERCHANT_ID', ''),
        'splitz_experiment_merchant_document_read_by_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_DOCUMENT_READ_BY_ID', ''),
        'splitz_experiment_stakeholder_read_by_id' => env('ASV_SPLITZ_EXPERIMENT_STAKEHOLDER_READ_BY_ID', ''),
        'splitz_experiment_stakeholder_read_by_merchant_id' => env('ASV_SPLITZ_EXPERIMENT_STAKEHOLDER_READ_BY_MERCHANT_ID', ''),
        'splitz_experiment_address_read_by_stakeholder_id' => env('ASV_SPLITZ_EXPERIMENT_ADDRESS_READ_BY_STAKEHOLDER_ID', ''),
        'splitz_experiment_merchant_detail_read_by_id' => env('ASV_SPLITZ_EXPERIMENT_MERCHANT_DETAIL_READ_BY_ID', '')
    ],

    'recon'         => [
        'api_key'               => env('RECON_SERVICE_API_AUTH_KEY'),
        'api_secret'            => env('RECON_SERVICE_API_AUTH_SECRET'),
        'url'               => env('RECON_SERVICE_URL'),
        'matcher_key'       => env('RECON_SERVICE_MATCHER_AUTH_KEY'),
        'matcher_secret'    => env('RECON_SERVICE_MATCHER_AUTH_SECRET'),
        // recon call to api secret
        'secret'  => env('API_RECON_SERVICE_AUTH_SECRET')
    ],

    'bbps' => [
        'provider' => env('BBPS_PROVIDER', 'mock'),
        'mock'   => [
        ],
        'setu'   => [
            'client_id'              => env('SETU_CLIENT_ID'),
            'client_secret'          => env('SETU_CLIENT_SECRET'),
            'impersonate_iframe_url' => env('SETU_IMPERSONATE_IFRAME_URL')
        ],
    ],

    'payout_link_customer_page' => [
        'secret'            => env('APP_PAYOUT_LINK_CUSTOMER_PAGE_SECRET'),

    ],

    'metro' => [
        'secret'            => env('APP_METRO_SECRET'),
    ],

    'affordability' => [
        'mock'   => env('AFFORDABILITY_SERVICE_MOCK', true),
        'secret' => env('AFFORDABILITY_SECRET'),
        'service_secret' => env('AFFORDABILITY_SERVICE_SECRET'),
        'url'    => env('AFFORDABILITY_SERVICE_URL'),
        'eligibility_url' => [
            'live' => env('ELIGIBILITY_SERVICE_LIVE_URL'),
            'test' => env('ELIGIBILITY_SERVICE_TEST_URL'),
        ],
    ],

    'checkout_service' => [
        'mock'     => env('CHECKOUT_SERVICE_MOCK', true),
        'url'      => env('CHECKOUT_SERVICE_URL'),
        'timeout'  => env('CHECKOUT_SERVICE_TIMEOUT'),
        // secret used by checkout service to call API monolith
        'secret'   => env('CHECKOUT_SERVICE_API_MONOLITH_SECRET'),
    ],

    'trusted_badge' => [
        'secret' => env('TRUSTED_BADGE_SECRET'),
    ],

    'tokenisation' => [
        'flipkart_secure_key' => env('FLIPKART_SECURE_KEY'),
        'flipkart_secure_IV'  => env('FLIPKART_SECURE_IV'),
    ],

    'shipping_service' => [
        'url'           => env('APP_SHIPPING_SERVICE_URL'),
        'username'      => 'api',
        'secret'        => env('APP_SHIPPING_SERVICE_SECRET'),
        'timeout'       => env('APP_SHIPPING_SERVICE_TIMEOUT', 10),
    ],

    'rto_prediction_service' => [
        'url'           => env('APP_RTO_PREDICTION_SERVICE_URL'),
        'username'      => 'api',
        'secret'        => env('APP_RTO_PREDICTION_SERVICE_SECRET'),
        'timeout'       => env('APP_RTO_PREDICTION_SERVICE_TIMEOUT', 10),
    ],

    'rto_prediction_service_api_web' => [
        'secret'        => env('APP_RTO_PREDICTION_SERVICE_API_WEB_SECRET')
    ],

    'address_service' => [
        'api' => [
            'url'           => env('APP_ADDRESS_SERVICE_API_URL'),
            'username'      => 'api',
            'secret'        => env('APP_ADDRESS_SERVICE_API_SECRET', ''),
            'timeout'       => env('APP_ADDRESS_SERVICE_API_TIMEOUT', 3),
        ],
        'secret'  => env('APP_ADDRESS_SERVICE_SECRET')
    ],

    'tokenization' => [
        'secret' => env('TOKENIZATION_SECRET'),
    ],

    'authz' => [
        'auth' => [
            'username' => env('AUTHZ_USER'),
            'password' => env('AUTHZ_SECRET'),
        ],
        'mock'     => env('AUTHZ_MOCK', true),
        'url'      => env('AUTHZ_BASE_URL'),
        'mock_url' => env('AUTHZ_BASE_MOCK_URL')
    ],

    'authzXPlatformEnforcer' => [
        'auth' => [
            'username' => env('AUTHZ_XPLATFORM_ENFORCER_USER'),
            'password' => env('AUTHZ_XPLATFORM_ENFORCER_SECRET'),
        ],
        'mock'     => env('AUTHZ_XPLATFORM_ENFORCER_MOCK', true),
        'url'      => env('AUTHZ_XPLATFORM_ENFORCER_BASE_URL'),
        'mock_url' => env('AUTHZ_XPLATFORM_ENFORCER_BASE_MOCK_URL')
    ],

    'authzXPlatformAdmin' => [
        'auth' => [
            'username' => env('AUTHZ_XPLATFORM_ADMIN_USER'),
            'password' => env('AUTHZ_XPLATFORM_ADMIN_SECRET'),
        ],
        'service_id' =>  env('AUTHZ_XPLATFORM_ADMIN_SERVICE_ID'),
        'mock'     => env('AUTHZ_XPLATFORM_ADMIN_MOCK', true),
        'url'      => env('AUTHZ_XPLATFORM_ADMIN_BASE_URL')
    ],



    'downtime_manager' => [
        'url' => env('DOWNTIME_MANAGER_URL'),
        'user' => env('DOWNTIME_MANAGER_USER'),
        'password' => env('DOWNTIME_MANAGER_PASSWORD')
    ],

    'success_rate' => [
        'host' => env('SUCCESS_RATE_HOST'),
        'basePath' => env('SUCCESS_RATE_BASE_PATH'),
        'user' => env('SUCCESS_RATE_USER'),
        'password' => env('SUCCESS_RATE_PASSWORD')
    ],

    'tls_config' => [
        'offline_challan_validate' => env('OFFLINE_CHALLAN_VALIDATE_DOMAINS'),
        'offline_payment_credit'   => env('OFFLINE_PAYMENT_CREDIT_DOMAINS')
    ],

    'whatcms' => [
        'base_url'      => env('WHATCMS_BASE_URL'),
        'secret'        => env('WHATCMS_API_KEY'),
    ],

    'consumer_app' => [
      'api' => [
          'url'           => env('CONSUMER_APP_SERVICE_API_URL'),
          'username'      => 'api',
          'secret'        => env('CONSUMER_APP_SERVICE_API_SECRET', ''),
          'timeout'       => env('CONSUMER_APP_SERVICE_API_TIMEOUT', 3),
      ],
      'secret'  => env('CONSUMER_APP_SERVICE_SECRET')
    ],

    'similarweb' => [
        'url'       => env('SIMILARWEB_API_URL'),
        'api_key'   => env('SIMILARWEB_API_KEY')
    ],

    'wda' => [
        'username' => env('TIDB_WDA_USERNAME'),
        'password' => env('TIDB_WDA_PASSWORD'),
        'base_uri' => env('TIDB_WDA_BASE_URL')
    ],

    'magic_checkout_service' => [
        'api' => [
            'url'           => env('APP_MAGIC_CHECKOUT_SERVICE_API_URL'),
            'username'      => 'api',
            'secret'        => env('APP_MAGIC_CHECKOUT_SERVICE_API_SECRET'),
            'timeout'       => env('APP_MAGIC_CHECKOUT_SERVICE_API_TIMEOUT', 20),
        ],
        'secret'        => env('APP_MAGIC_CHECKOUT_SERVICE_SECRET'),
    ],
];
