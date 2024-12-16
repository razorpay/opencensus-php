<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'https://api.razorpay.com'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('ENCRYPTION_KEY'),

    'cipher' => env('ENCRYPTION_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */

    'log' => env('APP_LOG', 'single'),

    /*
     |
     | Tells whether the application is deployed in amazon's cloud or
     | running locally
     |
     */
    'cloud' => env('CLOUD'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        // We use custom cache service provider
        // Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        RZP\Mail\MailServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        RZP\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /**
         * Third party providers
         * We can use an external service provider in one of our service providers because
         * of which we are initialising the external service providers before the
         * application service providers.
         */
        Aws\Laravel\AwsServiceProvider::class,
        Razorpay\Outbox\OutboxServiceProvider::class,
        Razorpay\Slack\Laravel\ServiceProvider::class,
        Schuppo\PasswordStrength\PasswordStrengthServiceProvider::class,
        anlutro\LaravelSettings\ServiceProvider::class,
        Sentry\Laravel\ServiceProvider::class,

        /**
         * Application Service Providers...
         */
        // RZP\Providers\AppServiceProvider::class,
        // RZP\Providers\AuthServiceProvider::class,
        RZP\Providers\FirstServiceProvider::class,
        RZP\Services\ApiServiceProvider::class,
        RZP\Providers\AffordabilityServiceProvider::class,
        RZP\Providers\EventServiceProvider::class,
        RZP\Providers\RouteServiceProvider::class,
        RZP\Providers\OpenCensusProvider::class,
        RZP\Http\BasicAuth\ServiceProvider::class,
        RZP\Services\DashboardServiceProvider::class,
        RZP\Models\User\RateLimitLoginSignup\Provider::class,

        // Makes blade sharper
        RZP\Providers\KnifeServiceProvider::class,
        \Conner\Tagging\Providers\TaggingServiceProvider::class,

        \LaravelFCM\FCMServiceProvider::class,
        \RZP\Providers\SqsRawServiceProvider::class,
        \RZP\Providers\SqsFifoServiceProvider::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Provider Manifest
    |--------------------------------------------------------------------------
    |
    | The service provider mani\fest is used by Laravel to lazy load service
    | providers which are not needed for each request, as well to keep a
    | list of all of the services. Here, you may set its storage spot.
    |
    */

    'manifest' => storage_path().'/meta',

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App'             => Illuminate\Support\Facades\App::class,
        'Artisan'         => Illuminate\Support\Facades\Artisan::class,
        'Auth'            => Illuminate\Support\Facades\Auth::class,
        'Blade'           => Illuminate\Support\Facades\Blade::class,
        'Cache'           => Illuminate\Support\Facades\Cache::class,
        'Config'          => Illuminate\Support\Facades\Config::class,
        'Cookie'          => Illuminate\Support\Facades\Cookie::class,
        'Crypt'           => RZP\Encryption\Facade::class,
        'DB'              => Illuminate\Support\Facades\DB::class,
        'Eloquent'        => Illuminate\Database\Eloquent\Model::class,
        'File'            => Illuminate\Support\Facades\File::class,
        'Gate'            => Illuminate\Support\Facades\Gate::class,
        'Hash'            => Illuminate\Support\Facades\Hash::class,
        'Lang'            => Illuminate\Support\Facades\Lang::class,
        'Log'             => Illuminate\Support\Facades\Log::class,
        'Password'        => Illuminate\Support\Facades\Password::class,
        'Queue'           => Illuminate\Support\Facades\Queue::class,
        'Redirect'        => Illuminate\Support\Facades\Redirect::class,
        'Redis'           => Illuminate\Support\Facades\Redis::class,
        'Request'         => Illuminate\Support\Facades\Request::class,
        'Response'        => Illuminate\Support\Facades\Response::class,
        'Route'           => Illuminate\Support\Facades\Route::class,
        'Schema'          => Illuminate\Support\Facades\Schema::class,
        'Session'         => Illuminate\Support\Facades\Session::class,
        'Sentry'          => Sentry\Laravel\Facade::class,
        'Storage'         => Illuminate\Support\Facades\Storage::class,
        'Str'             => Illuminate\Support\Str::class,
        'URL'             => Illuminate\Support\Facades\URL::class,
        'Validator'       => Illuminate\Support\Facades\Validator::class,
        'View'            => Illuminate\Support\Facades\View::class,

        // Application Facades
        'ApiResponse'     => RZP\Http\Response\Facade::class,

        // Custom Facades
        'AWS'             => Aws\Laravel\AwsFacade::class,
        'Slack'           => Razorpay\Slack\Laravel\Facade::class,
        'Event'           => RZP\Events\Facade::class,
        'Mail'            => RZP\Mail\Facade::class,
        'Workflow'        => RZP\Services\Workflow\Facade::class,
        'LaravelSettings' => anlutro\LaravelSettings\Facade::class,

        'FCM'             => LaravelFCM\Facades\FCM::class,
    ],

    'context'                             => env('CONTEXT'),

    'checkout'                            => env('CHECKOUT_URL'),

    'invoice'                             => env('INVOICE_URL'),

    'payment_link_hosted_base_url'        => env('PAYMENT_LINK_HOSTED_BASE_URL'),

    'payment_page_axis_hosted_base_url'   => env('PAYMENT_PAGE_AXIS_HOSTED_BASE_URL'),

    'razorpay_website_url'                => env('PL_DEMO_RAZORPAY_WEBSITE_URL'),

    'curlec_website_url'                  => env('PL_DEMO_CURLEC_WEBSITE_URL'),

    'razorpay_support_page_url'           => env('RAZORPAY_SUPPORT_PAGE_WEBSITE_URL'),

    'razorpay_instant_emi_url'            => env('RAZORPAY_INSTANT_EMI_URL'),

    'cdn_v1_url'                          => env('CDN_V1_URL'),

    'proxy_enabled'                       => env('PROXY_ENABLED'),

    'proxy_address'                       => env('PROXY_ADDRESS'),

    'subscription_proxy_timeout'          => env('SUBSCRIPTION_PROXY_TIMEOUT', 10),

    'throw_exception_in_testing'          => env('THROW_EXCEPTION_IN_TESTING', true),

    'financial_data_service_proxy_timeout'  => env('FINANCIAL_DATA_SERVICE_PROXY_TIMEOUT', 10),

    'sentry_mock'                         => env('SENTRY_MOCK', true),

    'data_store' => [
        'mock' => env('DATA_STORE_MOCK', false)
    ],

    'gateway_priority' => [
        'store_type' => env('GATEWAY_PRIORITY_STORE_TYPE')
    ],

    'mailchimp' => [
        'list_id'   => env('MAILCHIMP_LIST_ID', 'random_id'),
        'api_key'   => env('MAILCHIMP_API_TOKEN', 'mailchimp_token'),
        'mock'      => env('MAILCHIMP_MOCK', false),
    ],

    'signup' => [
        'nocaptcha_secret'          => env('NOCAPTCHA_SECRET', ''),
        'android_captcha_secret'    => env('ANDROID_NOCAPTCHA_SECRET', ''),
        'invisible_captcha_secret'  => env('INVISIBLE_CAPTCHA_SECRET', ''),
        'v3_captcha_secret'         => env('V3_CAPTCHA_SECRET', ''),
    ],

    'byok_nonrzp_orgs_encryption_keys' => [
        'encryption_key_CLTnQqDj9Si8bx' => env('ENCRYPTION_KEY_AXIS'),
    ],

    'customer_refund_details' => [
        'nocaptcha_secret' => env('WEBPAGE_GCAPTCHA_SECRET', ''),
    ],

    'query_cache' => [
        'mock' => env('QUERY_CACHE_MOCK', false),
    ],

    'pl_demo' => [
        'nocaptcha_secret'      => env('NOCAPTCHA_SECRET', ''),
    ],

    'qr_demo' => [
        'nocaptcha_secret'  => env('NOCAPTCHA_SECRET',''),
    ],

    'payment_page_allowed_cors_url' => [
        env('PAYMENT_LINK_HOSTED_BASE_URL'),
        env('PL_DEMO_RAZORPAY_WEBSITE_URL'),
        env('PL_DEMO_CURLEC_WEBSITE_URL'),
        env('PAYMENT_PAGE_AXIS_HOSTED_BASE_URL'),
        env('PAYMENT_HANDLE_HOSTED_BASE_URL'),
    ],

    'payment_store_allowed_cors_url' => [
        env('PAYMENT_STORE_HOSTED_BASE_URL'),
    ],

    'payment_handle' => [
        'secret' => env('PAYMENT_HANDLE_AMOUNT_SECRET')
    ],

    'cross_border_handle' => [
        'aes_encryption_key' => env('CROSS_BORDER_AES_ENCRYPTION_KEY')
    ],

    'apps_default_sender_email_address' => env('APPS_DEFAULT_SENDER_EMAIL_ADDRESS'),

    'amount_difference_allowed_authorized' => ['EQ8AzfZip2meDu', 'FBYspBmKlWefX9'],

    'curlec_customer_flagging_report_url' => env('CURLEC_CUSTOMER_FLAGGING_REPORT_URL'),

    'customer_flagging_report_url' => env('CUSTOMER_FLAGGING_REPORT_URL'),

    'keyless_header' =>  [
        'identifier'    => env('KEYLESS_HEADER_IDENTIFIER'),
        'sender'    =>  [
            'public_key'    => env('KEYLESS_HEADER_SENDER_PUBLIC'),
            'private_key'   => env('KEYLESS_HEADER_SENDER_PRIVATE')
        ],
        'receiver'  =>  [
            'public_key'    => env('KEYLESS_HEADER_RECEIVER_PUBLIC'),
        ],
    ],

    'payment_store_hosted_base_url' => env('PAYMENT_STORE_HOSTED_BASE_URL'),

    'payment_handle_hosted_base_url' => env('PAYMENT_HANDLE_HOSTED_BASE_URL'),

    'consent_view_splitz_experiment_id' => env('CONSENT_VIEW_SPLITZ_EXPERIMENT_ID'),

    'rtb_splitz_experiment_id' => env('RTB_SPLITZ_EXPERIMENT_ID'),

    'rtb_mailers_splitz_experiment_id' => env('RTB_MAILERS_SPLITZ_EXPERIMENT_ID'),

    'settle_to_partner_alerting_experiment_id' => env('SETTLE_TO_PARTNER_ALERTING_EXPERIMENT_ID'),

    'add_subm_ratelimiting_experiment_id' => env('ADD_SUBM_RATELIMITING_EXPERIMENT_ID'),

    'admin_submerchant_bulk_increase_resources_exp_id' => env('ADMIN_SUBMERCHANT_BULK_INCREASE_RESOURCES_EXP_ID'),

    'attach_view_only_role_banking_account_exp_id' => env('ATTACH_VIEW_ONLY_ROLE_BANKING_ACCOUNT'),

    'submerchant_bulk_validation_status_update_exp_id' => env('SUBMERCHANT_BULK_VALIDATION_STATUS_UPDATE_EXP_ID'),

    'default_payment_config_for_subm_exp_id' => env('DEFAULT_PAYMENT_CONFIG_FOR_SUBM_EXP_ID'),

    'partners_excluded_from_instant_act_v2_api_exp_id'  => env('PARTNERS_EXCLUDED_FROM_INSTANT_ACT_V2_API_EXP_ID'),

    'send_sms_on_commission_invoice_issued_exp_id' => env('SEND_SMS_ON_COMMISSION_INVOICE_ISSUED_EXP_ID'),

    'redirect_malaysia_card_payments_via_api' => env('REDIRECT_MALAYSIA_CARD_PAYMENTS_VIA_API'),

    'enabled_recurring_card_types_malaysia' => env('ENABLED_RECURRING_CARD_TYPES_MALAYSIA'),

    'send_sms_whatsapp_partner_submerchant_onboarding_events' => env('SEND_SMS_WHATSAPP_PARTNER_SUBMERCHANT_ONBOARDING_EVENTS'),

    'send_partner_submerchant_needs_clarification_communications' => env('SEND_PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION_COMMUNICATIONS'),

    'send_commission_invoice_reminders_exp_id' => env('SEND_COMMISSION_INVOICE_REMINDERS_EXP_ID'),

    'ledger_makeshift_dual_write_enabled' => env('LEDGER_MAKESHIFT_DUAL_WRITE_ENABLED'),

    'api_ledger_dual_write_rearch' => env('API_LEDGER_DUAL_WRITE_REARCH'),

    'partner_regenerate_referrals_links_exp_id' => env('PARTNER_REGENERATE_REFERRAL_LINKS_EXP_ID'),

    'partner_config_auditing_experiment_id' => env('PARTNER_CONFIG_AUDITING_EXPERIMENT_ID'),

    'platform_partner_oauth_custom_pricing_plan' => env('PLATFORM_PARTNER_OAUTH_CUSTOM_PRICING_PLAN'),

    'partner_entities_partnership_service_sync' => env('PARTNER_ENTITIES_PARTNERSHIP_SERVICE_SYNC'),

    'sub_merchant_activation_auto_approval_checker' => env('SUB_MERCHANT_ACTIVATION_AUTO_APPROVAL_CHECKER'),

    'partner_weekly_activation_summary_datalake_exp_id' => env('PARTNER_WEEKLY_ACTIVATION_SUMMARY_DATALAKE_EXP_ID'),

    'enable_merchant_dashboard_timeout_experiment_id' => env('ENABLE_MERCHANT_DASHBOARD_TIMEOUT_EXPERIMENT_ID'),

    'submerchant_ownership_transfer_experiment_id' => env('SUBMERCHANT_OWNERSHIP_TRANSFER_EXPERIMENT_ID'),

    'remove_partner_user_from_merchant_manage_team_experiment_id' => env('REMOVE_PARTNER_USER_FROM_MERCHANT_MANAGE_TEAM_EXPERIMENT_ID'),

    'excluded_partners_from_providing_subm_ip_experiment_id' => env('EXCLUDED_PARTNERS_FROM_PROVIDING_SUBM_IP_EXPERIMENT_ID'),

    'partnerships_sales_poc_experiment_id'  => env('PARTNERSHIPS_SALES_POC_EXPERIMENT_ID'),

    'partnerships_for_marketplace_transfer_experiment_id'   => env('PARTNERSHIPS_FOR_MARKETPLACE_TRANSFER_EXPERIMENT_ID'),

    'submerchant_payment_manual_settlement_experiment_id'   => env('SUBMERCHANT_PAYMENT_MANUAL_SETTLEMENT_EXPERIMENT_ID'),

    '1cc_splitz_experiment_id' => env('MAGIC_CHECKOUT_SPLITZ_EXPERIMENT_ID'),

    '1cc_city_autopopulate_splitz_experiment_id' => env('MAGIC_CHECKOUT_DISABLE_AUTOPOPULATE_EXP_ID'),

    '1cc_address_flow_exp_splitz_experiment_id' => env('MAGIC_CHECKOUT_ADDRESS_FLOW_EXP_ID'),

    '1cc_pg_router_ramp_up_exp_id' => env('MAGIC_CHECKOUT_PG_ROUTER_RAMP_EXP_ID'),

    'shopify_1cc_sqs_splitz_experiment_id' => env('SHOPIFY_1CC_SQS_SPLITZ_EXPERIMENT_ID'),

    'void_refund_avs_failed_experiment_id' => env('VOID_REFUND_AVS_FAILED_EXPERIMENT_ID'),

    'cmma_limit_breach_trigger_experiment_id' => env('CMMA_LIMIT_BREACH_TRIGGER_EXPERIMENT_ID'),

    'cmma_limit_breach_trigger_new_experiment_id' => env('CMMA_LIMIT_BREACH_TRIGGER_NEW_EXPERIMENT_ID'),

    'pgos_shadow_mode_experiment_id' => env('PGOS_SHADOW_MODE_EXPERIMENT_ID'),

    'pgos_live_mode_experiment_id' => env('PGOS_LIVE_MODE_EXPERIMENT_ID'),

    'apply_mutex_on_pgos_dual_write_experiment_id' => env('APPLY_MUTEX_ON_PGOS_DUAL_WRITE_EXPERIMENT_ID'),

    'apply_mutex_on_merchant_entities_update_experiment_id' => env('APPLY_MUTEX_ON_MERCHANT_ENTITIES_UPDATE_EXPERIMENT_ID'),

    'easy_submerchant_pgos_live_mode_experiment_id' => env('EASY_SUBMERCHANT_PGOS_LIVE_MODE_EXPERIMENT_ID'),

    'linked_account_modular_onboarding_activate_experiment_id' => env('LINKED_ACCOUNT_MODULAR_ONBOARDING_ACTIVATE_EXPERIMENT_ID', false),

    'pgos_phantom_live_mode_experiment_id' => env('PGOS_PHANTOM_LIVE_MODE_EXPERIMENT_ID'),

    'others_m3_experiment_id' => env('OTHERS_M3_EXPERIMENT_ID'),

    'risk_tags_check_experiment_id' => env('RISK_TAGS_CHECK_EXPERIMENT_ID'),

    'override_deactivate_experiment_id' => env('OVERRIDE_DEACTIVATE_EXPERIMENT_ID'),

    'wda_migration_acquisition_splitz_exp_id' => env('WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID'),

    'hybrid_data_querying_splitz_experiment_id'=> env('HYBRID_DATA_QUERYING_SPLITZ_EXPERIMENT_ID'),

    'ezetap_merchant_id'=> env('EZETAP_MERCHANT_ID'),

    'capital_migration_experiment_id' => env('CAPITAL_MIGRATION_EXPERIMENT_ID'),

    'cmma_soft_limit_breach_trigger_experiment_id' => env('CMMA_SOFT_LIMIT_BREACH_TRIGGER_EXPERIMENT_ID'),

    'cmma_amp_trigger_experiment_id' => env('CMMA_AMP_TRIGGER_EXPERIMENT_ID'),

    'cmma_auto_kyc_failure_trigger_experiment_id' => env('CMMA_AUTO_KYC_FAILURE_TRIGGER_EXPERIMENT_ID'),

    'cmma_escalation_new_process_id' => env('CMMA_ESCALATION_NEW_PROCESS_ID'),

    'cmma_escalation_process_id' => env('CMMA_ESCALATION_PROCESS_ID'),

    'care_chat_migration_splitz_experiment_id' => env('CARE_CHAT_MIGRATION_SPLITZ_EXPERIMENT_ID'),

    'payment_handle_domain' => env('PAYMENT_HANDLE_DOMAIN'),

    'commission_invoice_bucket_migration_exp_id'  => env('COMMISSION_INVOICE_BUCKET_MIGRATION_EXP_ID'),

    'rbl_on_bas_exp_id' => env('RBL_ON_BAS_EXP_ID'),

    'vendor_payment_metro_to_kafka_exp_id' => env('VENDOR_PAYMENT_SPLITZ_EXPERIMENT_METRO_TO_KAFKA'),

    'cmma_metro_migrate_out_experiment_id' => env('CMMA_METRO_MIGRATE_OUT_EXPERIMENT_ID'),

    'payment_handle_order_creation_experiment_id' => env('PAYMENT_HANDLE_ORDER_CREATION_EXPERIMENT_ID'),

    'under_review_communications_from_api_exp_id' => env('SEND_MERCHANT_NOTIFICATIONS_FROM_API_EXPERIMENT_ID'),

    'rejected_communications_from_api_exp_id' => env('SEND_MERCHANT_NOTIFICATIONS_FROM_API_EXPERIMENT_ID'),

    'needs_clarification_communications_from_api_exp_id' => env('SEND_NC_MERCHANT_NOTIFICATIONS_FROM_API_EXPERIMENT_ID'),

    'cac_blacklist_exp_id' => env('CAC_BLACKLIST_EXP_ID'),


    'nocode' => [
        'cache' => [
            'slug_ttl'      => env('NOCODE_SLUG_CACHE_TTL', 86400),
            'prefix'        => env('NOCODE_CACHE_PREFIX', 'NOCODE'),
            'hosted_ttl'    => env('NOCODE_HOSTED_CACHE_TTL', 3600),
            'custom_url_ttl'    => env('NOCODE_CUSTOM_URL_CACHE_TTL', 86400),
        ]
    ],

    'db_migration_metrics_sampling_percent' => env('DB_MIGRATION_METRIC_SAMPLING_PERCENT', 1.0),

    'product_config_issue_exp_id'                           => env('PRODUCT_CONFIG_ISSUE_EXP_ID'),
    'user_role_migration_for_x_exp_id'                      => env('USER_ROLE_MIGRATION_FOR_X_EXP_ID'),
    'partner_type_bulk_migration_exp_id'                    => env('PARTNER_TYPE_BULK_MIGRATION_EXP_ID'),
    'partner_type_switch_exp_id'                            => env('PARTNER_TYPE_SWITCH_EXP_ID'),
    'submerchant_fetch_multiple_optimisation_exp_id'        => env('SUBMERCHANT_FETCH_MULTIPLE_OPTIMISATION_EXP_ID'),
    'finance_approval_removal_exp_id'                       => env('FINANCE_APPROVAL_REMOVAL_EXP_ID'),
    'merchant_policies_exp_id'                              => env('MERCHANT_POLICIES_EXP_ID'),
    'product_led_mail_communication'                        => env('PRODUCT_LED_MAIL_COMMUNICATION'),
    'merchant_activation_manual_override'                   => env('MERCHANT_ACTIVATION_MANUAL_OVERRIDE'),
    'merchant_activation_ineligible'                        => env('MERCHANT_ACTIVATION_INELIGIBLE'),
    'merchant_consent_v2'                                   => env('MERCHANT_CONSENT_V2'),
    'merchant_consent_v2_notification'                      => env('MERCHANT_CONSENT_V2_NOTIFICATION'),
    'merchant_consent_privacy_template_id'                  => env('MERCHANT_CONSENT_PRIVACY_TEMPLATE_ID'),
    'merchant_consent_terms_template_id'                    => env('MERCHANT_CONSENT_TERMS_TEMPLATE_ID'),
    'partnership_consent_v2_experiment'                     => env('PARTNERSHIP_CONSENT_V2_EXPERIMENT'),
    'partnership_consent_terms_template_id'                 => env('PARTNERSHIP_CONSENT_TERMS_TEMPLATE_ID'),
    'partnership_consent_oauth_template_id'                 => env('PARTNERSHIP_CONSENT_OAUTH_TEMPLATE_ID'),
    'partnership_consent_switch_template_id'                => env('PARTNERSHIP_CONSENT_SWITCH_TEMPLATE_ID'),
    'partnership_oauth_consent_read_only_template_id'       => env('PARTNERSHIP_OAUTH_CONSENT_READ_ONLY_TEMPLATE_ID'),
    'partnership_oauth_consent_read_write_template_id'      => env('PARTNERSHIP_OAUTH_CONSENT_READ_WRITE_TEMPLATE_ID'),
    'partnership_aggregator_consent_template_id'            => env('PARTNERSHIP_AGGREGATOR_CONSENT_TEMPLATE_ID'),

    'partner_invoice_auto_approval_exp_id'                  => env('PARTNER_INVOICE_AUTO_APPROVAL_EXP_ID'),
    'cmma_post_onboarding_foh_removal_splitz_experiment_id' => env('CMMA_POST_ONBOARDING_FOH_REMOVAL_SPLITZ_EXPERIMENT_ID'),
    'user_fetch_merchant_list_limit_exp_id'                 => env('USER_FETCH_MERCHANT_LIST_LIMIT_EXP_ID'),
    'enable_signups'                                        => env('ENABLE_SIGNUPS'),

    'permission_id_edit_merchant_hold_funds'  => env('EDIT_MERCHANT_HOLD_FUNDS'),
    'permission_id_edit_merchant_suspend'     => env('EDIT_MERCHANT_SUSPEND'),
    'permission_id_merchant_risk_alert_foh'   => env('MERCHANT_RISK_ALERT_FOH'),
    'permission_id_edit_merchant_disable_live'=> env('EDIT_MERCHANT_DISABLE_LIVE'),

    'merchant_kyc_update_to_partner_exp_id'            => env('MERCHANT_KYC_UPDATE_TO_PARTNER_EXP_ID'),
    'send_weekly_activation_summary_to_partner_exp_id' => env('SEND_WEEKLY_ACTIVATION_SUMMARY_TO_PARTNER_EXP_ID'),

    'subm_unlinking_request_to_nss_exp_id'     => env('SUBM_UNLINKING_REQUEST_TO_NSS_EXP_ID'),
    'partner_onboard_email_experiment_id'     => env('PARTNER_ONBOARD_EMAIL_EXPERIMENT_ID'),
    'magic_checkout' => [
        'magic_pg_order_mutex_ttl'     => env('MAGIC_PG_ORDER_MUTEX_TTL'),
        'magic_pg_order_call_ttl'      => env('MAGIC_PG_ORDER_CALL_TTL'),
        'magic_pg_order_mutex_retries' => env('MAGIC_PG_ORDER_MUTEX_RETRIES'),
    ],

    'email_less_checkout_experiment_id' => env('EMAIL_LESS_CHECKOUT_EXPERIMENT_ID'),

    'checkout_cvv_less_splitz_experiment_id' => env('CHECKOUT_CVV_LESS_SPLITZ_EXPERIMENT_ID'),

    'checkout_cvv_less_rupay_splitz_experiment_id' => env('CHECKOUT_CVV_LESS_RUPAY_SPLITZ_EXPERIMENT_ID'),

    'checkout_redesign_v1_5_splitz_experiment_id' => env('CHECKOUT_REDESIGN_V1_5_SPLITZ_EXPERIMENT_ID'),

    'checkout_upi_qr_v2_splitz_experiment_id'     => env('CHECKOUT_UPI_QR_V2_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_redesign_v1_5_splitz_experiment_id' => env('CHECKOUT_RECURRING_REDESIGN_V1_5_SPLITZ_EXPERIMENT_ID'),

    'checkout_reuse_upi_payment_id_splitz_experiment_id' => env('CHECKOUT_REUSE_UPI_PAYMENT_ID_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_upi_intent_splitz_experiment_id' => env('CHECKOUT_RECURRING_UPI_INTENT_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_intl_verify_phone_splitz_experiment_id' => env('CHECKOUT_RECURRING_INTL_VERIFY_PHONE_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_upi_qr_splitz_experiment_id' => env('CHECKOUT_RECURRING_UPI_QR_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_payment_method_configuration_splitz_experiment_id' => env('CHECKOUT_RECURRING_PAYMENT_METHOD_CONFIGURATION_SPLITZ_EXPERIMENT_ID'),

    'checkout_dcc_vas_merchants_splitz_experiment_id' => env('CHECKOUT_DCC_VAS_MERCHANTS_SPLITZ_EXPERIMENT_ID'),

    'checkout_recurring_upi_autopay_psp_splitz_experiment_id' => env('CHECKOUT_RECURRING_UPI_AUTOPAY_PSP_SPLITZ_EXPERIMENT_ID'),

    'checkout_banking_redesign_v1_5_splitz_experiment_id' => env('CHECKOUT_BANKING_REDESIGN_V1_5_SPLITZ_EXPERIMENT_ID'),

    'truecaller_standard_checkout_for_prefill_splitz_experiment_id'    => env('TRUECALLER_STANDARD_CHECKOUT_FOR_PREFILL_SPLITZ_EXPERIMENT_ID'),

    'truecaller_standard_checkout_for_non_prefill_splitz_experiment_id'    => env('TRUECALLER_STANDARD_CHECKOUT_FOR_NON_PREFILL_SPLITZ_EXPERIMENT_ID'),

    'truecaller_1cc_for_prefill_splitz_experiment_id'    => env('TRUECALLER_1CC_FOR_PREFILL_SPLITZ_EXPERIMENT_ID'),

    'truecaller_1cc_for_non_prefill_splitz_experiment_id'    => env('TRUECALLER_1CC_FOR_NON_PREFILL_SPLITZ_EXPERIMENT_ID'),

    'checkout_enable_rudderstack_plugin_splitz_experiment_id' => env('CHECKOUT_ENABLE_RUDDERSTACK_PLUGIN_SPLITZ_EXPERIMENT_ID'),

    'checkout_downtime_splitz_experiment_id' => env('CHECKOUT_DOWNTIME_SPLITZ_EXPERIMENT_ID'),

    'checkout_enable_auto_submit_splitz_experiment_id' => env('CHECKOUT_ENABLE_AUTO_SUBMIT_SPLITZ_EXPERIMENT_ID'),

    'checkout_upi_number_splitz_experiment_id' => env('CHECKOUT_UPI_NUMBER_SPLITZ_EXPERIMENT_ID'),

    'checkout_upi_turbo_splitz_experiment_id' => env('CHECKOUT_UPI_TURBO_SPLITZ_EXPERIMENT_ID'),

    'checkout_offers_ux_splitz_experiment_id' => env('CHECKOUT_OFFERS_UX_SPLITZ_EXPERIMENT_ID'),

    'checkout_upi_number_contact_blacklist_splitz_experiment_id' => env('CHECKOUT_UPI_NUMBER_CONTACT_BLACKLIST_SPLITZ_EXPERIMENT_ID'),

    'dedicated_terminal_qr_code_splitz_experiment_id' => env('DEDICATED_TERMINAL_QR_CODE_SPLITZ_EXPERIMENT_ID'),

    'enable_ezetap_notification_splitz_experiment_id' => env('ENABLE_EZETAP_NOTIFICATION_SPLITZ_EXPERIMENT_ID'),

    'qr_code_status_check_splitz_experiment_id' => env('QR_CODE_STATUS_CHECK_SPLITZ_EXPERIMENT_ID'),

    'checkout_service_preferences_splitz_experiment_id' => env('CHECKOUT_SERVICE_PREFERENCES_SPLITZ_EXPERIMENT_ID'),

    'checkout_enable_otp_auto_read_and_auto_submit_splitz_experiment_id' => env('CHECKOUT_ENABLE_OTP_AUTO_READ_AND_AUTO_SUBMIT_SPLITZ_EXPERIMENT_ID'),

    'checkout_order_signature_experiment_id' => env('CHECKOUT_ORDER_SIGNATURE_EXPERIMENT_ID'),

    'checkout_signature_payment_experiment_id' => env('CHECKOUT_SIGNATURE_PAYMENT_EXPERIMENT_ID'),

    'nocodeapps_ratelimit_experiment_id' => env('NOCODEAPPS_RATELIMIT_EXPERIMENT_ID'),

    'nocodeapps_block_keywords_experiment_id' => env('NOCODEAPPS_BLOCK_KEYWORDS_EXPERIMENT_ID'),

    'send_submerchant_first_transaction_segment_event' => env('SEND_SUBMERCHANT_FIRST_TRANSACTION_SEGMENT_EVENT'),

    'dcc_recurring_on_auto_direct_experiment_id' => env('DCC_RECURRING_ON_AUTO_DIRECT_EXPERIMENT_ID'),

    'dcc_recurring_on_auto_direct_experiment_id' => env('DCC_RECURRING_ON_AUTO_DIRECT_EXPERIMENT_ID'),

    'cc_on_upi_pricing_splitz_experiment_id' => env('CC_ON_UPI_PRICING_SPLITZ_EXPERIMENT_ID'),

    'credit_line_on_upi_pricing_splitz_experiment_id' => env('CREDIT_LINE_ON_UPI_PRICING_SPLITZ_EXPERIMENT_ID'),

    'allow_offers_on_rearch_ups_splitz_experiment_id' => env('ALLOW_OFFERS_ON_REARCH_UPS_SPLITZ_EXPERIMENT_ID'),

    'checkout_netbanking_corporate_splitz_experiment_id' => env('CHECKOUT_NETBANKING_CORPORATE_SPLITZ_EXPERIMENT_ID'),

    'sync_orghostname_experiment' => env('SYNC_ORGHOSTNAME_EXPERIMENT'),

    'upi_rrn_search_experiment_id' => env('UPI_RRN_SEARCH_EXPERIMENT_ID'),

    'display_upi_payer_name_experiment_id' => env('DISPLAY_UPI_PAYER_NAME_EXPERIMENT_ID'),

    'allow_order_transfers_on_rearch_ups_splitz_experiment_id' => env('ALLOW_ORDER_TRANSFERS_ON_REARCH_UPS_SPLITZ_EXPERIMENT_ID'),

    'validate_vpa_splitz_experiment_id' => env('VALIDATE_VPA_SPLITZ_EXPERIMENT_ID'),

    'pricing_fallback_standard_plan_experiment_id' => env('PRICING_FALLBACK_STANDARD_PLAN_EXPERIMENT_ID'),

    'pricing_writes_experiment_id' => env('PRICING_WRITES_EXPERIMENT_ID'),

    'pricing_reads_experiment_id' => env('PRICING_READS_EXPERIMENT_ID'),

    'pricing_fee_round_experiment_id' => env('PRICING_FEE_ROUND_EXPERIMENT_ID'),

    'fetch_merchant_consent_from_pgos_experimant_id' => env('FETCH_MERCHANT_CONSENTS_FROM_PGOS_EXPERIMENT_ID'),

    'ppi_wallet_on_upi_pricing_splitz_experiment_id' => env('PPI_WALLET_ON_UPI_PRICING_SPLITZ_EXPERIMENT_ID'),

    'dcc_on_auto_subscription_payments_experiment_id' => env('DCC_ON_AUTO_SUBSCRIPTION_PAYMENTS_EXPERIMENT_ID'),

    'partner_independent_kyc_exp_id' => env('PARTNER_INDEPENDENT_KYC_EXP_ID'),

    'optimise_submerchant_create_exp_id' => env('OPTIMISE_SUBMERCHANT_CREATE_EXP_ID'),

    'route_partnership_v1_guards_exp_id' => env('ROUTE_PARTNERSHIP_V1_GUARD_EXP_ID'),

    'skip_avs_on_3ds_experiment_id' => env('SKIP_AVS_CHECK_ON_3DS_EXPERIMENT_ID'),

    '1cc_coupon_drop_off_splitz_experiment_id' => env('MAGIC_CHECKOUT_COUPONS_DROP_OFF_EXP_ID'),

    'prts_commission_dual_write_exp_id' => env('PRTS_COMMISSION_DUAL_WRITE_EXP_ID'),

    'prts_switch_over_partnerships_exp_id' => env('PRTS_SWITCH_OVER_PARTNERSHIPS_EXP_ID'),

    'prts_commission_invoice_exp_id' => env('PRTS_COMMISSION_INVOICE_SHADOW_PHASE_EXP_ID'),

    'prts_read_api_exp_id' => env('PRTS_READ_API_EXP_ID'),

    'prts_commission_shadow_phase_exp_id' => env('PRTS_COMMISSION_SHADOW_PHASE_EXP_ID'),

    'prts_onboard_new_partner_to_ledger_exp_id' => env('PRTS_ONBOARD_NEW_PARTNER_TO_LEDGER_EXP_ID'),

    'partnership_service_commission_shadow_phase_exp_id' => env('PARTNERSHIP_SERVICE_COMMISSION_SHADOW_PHASE_EXP_ID'),

    'prts_commission_reverse_shadow_exp_id' => env('PRTS_COMMISSION__REVERSE_SHADOW_EXP_ID'),

    'prts_commission_calculator_exp_id' => env('PRTS_COMMISSION_CALCULATOR_EXP_ID'),

    'new_commission_logic_exp_id'           => env('NEW_COMMISSION_LOGIC_EXP_ID'),

    'commission_invoice_events_to_kafka_exp_id'  => env('COMMISSION_INVOICE_EVENTS_TO_KAFKA_EXP_ID'),

    'commission_reversal_for_refund_exp_id' => env('COMMISSION_REVERSAL_FOR_REFUNDS_EXP_ID'),

    'magic_checkout_woocommerce_giftcard_url'       => env('MAGIC_CHECKOUT_WOOCOMMERCE_GIFTCARD_URL'),

    '1cc_multiple_shipping_splitz_experiment_id' => env('MAGIC_CHECKOUT_MULTIPLE_SHIPPING_EXP_ID'),

    'partner_submerchant_whitelabel_onboarding' => env('PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING'),

    'partner_submerchant_oauth_onboarding' => env('PARTNER_SUBMERCHANT_OAUTH_ONBOARDING'),

    'submerchant_onboarding_resume_experiment_id' => env('SUBMERCHANT_ONBOARDING_RESUME_EXPERIMENT_ID'),

    'magic_apply_coupon_experiment_id' => env('MAGIC_APPLY_COUPON_EXPERIMENT_ID'),

    'cross_border_dcc_rearch_experiment_id' => env('CROSS_BORDER_DCC_REARCH_EXPERIMENT_ID'),

    'cross_border_s2s_dcc_rearch_experiment_id' => env('CROSS_BORDER_S2S_DCC_REARCH_EXPERIMENT_ID'),

    'cross_border_mcc_payment_via_rearch_experiment_id' => env('CROSS_BORDER_MCC_PAYMENT_VIA_REARCH_EXPERIMENT_ID'),

    'cross_border_mcc_rearch_experiment_id' => env('CROSS_BORDER_MCC_REARCH_EXPERIMENT_ID'),

    'cross_border_flows_experiment_id' => env('CROSS_BORDER_FLOWS_EXPERIMENT_ID'),

    'cross_border_s2s_mcc_rearch_experiment_id' => env('CROSS_BORDER_S2S_MCC_REARCH_EXPERIMENT_ID'),

    'cross_border_cfb_intl_cls_experiment_id' => env('CROSS_BORDER_CFB_INTL_CLS_EXPERIMENT_ID'),

    'cross_border_dcc_mcc_rearch_experiment_id' => env('CROSS_BORDER_DCC_MCC_REARCH_EXPERIMENT_ID'),

    'cross_border_s2s_dcc_mcc_rearch_experiment_id' => env('CROSS_BORDER_S2S_DCC_MCC_REARCH_EXPERIMENT_ID'),

    'cross_border_mcc_parity_check_experiment_id' => env('CROSS_BORDER_MCC_PARITY_CHECK_EXPERIMENT_ID'),

    'cross_border_razorpayjs_rearch_experiment_id' => env('CROSS_BORDER_RAZORPAYJS_REARCH_EXPERIMENT_ID'),

    'cross_border_other_libraries_rearch_experiment_id' => env('CROSS_BORDER_OTHER_LIBRARIES_REARCH_EXPERIMENT_ID'),

    'cross_border_skip_address_check_experiment_id' => env('CROSS_BORDER_SKIP_ADDRESS_CHECK_EXPERIMENT_ID'),

    'cross_border_skip_fee_bearer_check_experiment_id' => env('CROSS_BORDER_SKIP_FEE_BEARER_CHECK_EXPERIMENT_ID'),

    'cross_border_payment_fee_fix_experiment_id' => env('CROSS_BORDER_PAYMENT_FEE_FIX_EXPERIMENT_ID'),

    'show_upi_autopay_method_on_dashboard' => env('SHOW_UPI_AUTOPAY_METHOD_ON_DASHBOARD'),

    'enable_force_auth_on_upi_autopay' => env('ENABLE_FORCE_AUTH_ON_UPI_AUTOPAY'),

    'upi_autopay_one_time_mandate_reattempt_interval' => env('UPI_AUTOPAY_ONE_TIME_MANDATE_REATTEMPT_INTERVAL'),

    'upi_autopay_payment_remark' => env('UPI_AUTOPAY_PAYMENT_REMARK'),

    'upi_autopay_revoke_pause_token' => env('UPI_AUTOPAY_REVOKE_PAUSE_TOKEN'),

    'upi_autopay_revokable_feature' => env('UPI_AUTOPAY_REVOKABLE_FEATURE'),

    'upi_autopay_increase_debit_retries' => env('UPI_AUTOPAY_INCREASE_DEBIT_RETRIES'),

    'upi_autopay_increase_debit_retries_time_gap' => env('UPI_AUTOPAY_INCREASE_DEBIT_RETRIES_TIME_GAP'),

    'upi_autopay_gateway_refund' => env('UPI_AUTOPAY_GATEWAY_REFUND'),

    'upi_autopay_promotional_qr' => env('UPI_AUTOPAY_PROMOTIONAL_QR'),

    'upi_autopay_promotional_intent' => env('UPI_AUTOPAY_PROMOTIONAL_INTENT'),

    'upi_autopay_pricing_blacklist' => env('UPI_AUTOPAY_PRICING_BLACKLIST'),

    'upi_autopay_disable_max_amount_blacklist' => env('UPI_AUTOPAY_DISABLE_MAX_AMOUNT_BLACKLIST'),

    'fetch_flows_api_forex_rates_from_rearch_experiment_id' => env('FETCH_FLOWS_API_FOREX_RATES_FROM_REARCH_EXPERIMENT_ID'),

    'capital_partnership_experiment_id' => env('CAPITAL_PARTNERSHIP_EXPERIMENT_ID'),

    'pos_partnership_experiment_id' => env('POS_PARTNERSHIP_EXPERIMENT_ID'),

    'pos_enabled_experiment_id' => env('POS_ENABLED_EXPERIMENT_ID'),

    'easy_kyc_access_referral_experiment_id' => env('EASY_KYC_ACCESS_REFERRAL_EXP_ID'),

    'read_from_ti_db_experiment_id' => env('READ_FROM_TI_DB_EXPERIMENT_ID'),

    '1cc_enable_v165_splitz_experiment_id' => env('MAGIC_CHECKOUT_ENABLE_V165_EXP_ID'),

    '1cc_coupons_with_se_splitz_experiment_id' => env('MAGIC_CHECKOUT_COUPONS_WITH_SCRIPT_EDITOR_EXP_ID'),

    '1cc_order_default_pending_splitz_experiment_id' => env('MAGIC_CHECKOUT_ORDER_WITH_PENDING_STATUS_EXP_ID'),

    '1cc_allow_partial_refund_splitz_experiment_id' => env('MAGIC_CHECKOUT_ALLOW_PARTIAL_REFUND_EXP_ID'),
    'magic_use_delegate_access_token_experiment_id' => env('MAGIC_USE_DELEGATE_ACCESS_TOKEN_EXP_ID'),
    'magic_offers_fix_splitz_experiment_id'  => env('MAGIC_CHECKOUT_OFFERS_FIX_EXP_ID'),

    'vendor_payment_via_corp_card_experiment_id' => env('VENDOR_PAYMENT_VIA_CORP_CARD_EXPERIMENT_ID'),

    'double_fta_fix_experiment_id' => env('DOUBLE_FTA_FIX_EXPERIMENT_ID'),

    'account_statements_source_event_experiment_id' => env('ACCOUNT_STATEMENTS_SOURCE_EVENT_EXPERIMENT_ID'),

    'mutex_lock_contact_experiment_id' => env('MUTEX_LOCK_CONTACT_EXPERIMENT_ID'),

    'mutex_lock_fund_account_experiment_id' => env('MUTEX_LOCK_FUND_ACCOUNT_EXPERIMENT_ID'),

    '1cc_shipping_info_migration_splitz_experiment_id' => env('MAGIC_CHECKOUT_SHIPPING_INFO_MIGRATION_EXP_ID'),

    'magic_show_coupon_callout_experiment_id'  => env('MAGIC_SHOW_COUPON_CALLOUT_EXP_ID'),

    'merchant_automation_activation_exp_id' => env('MERCHANT_AUTOMATION_ACTIVATION_EXP_ID'),

    'nocodeapp_pricing_exp_id' => env('NOCODEAPP_PRICING_EXP_ID'),

    'nocodeapp_pricing_plans_exp_id' => env('NOCODEAPP_PRICING_PLANS_EXP_ID'),

    'no_website_merchant_automation_activation_exp_id' => env('NO_WEBSITE_MERCHANT_AUTOMATION_ACTIVATION_EXP_ID'),

    'pgos_migration_dual_writing_exp_id' => env('PGOS_MIGRATION_DUAL_WRITING_EXP_ID'),

    'merchant_business_category_v3_revamp_exp_id' => env('MERCHANT_BUSINESS_CATEGORY_V3_REVAMP_EXP_ID'),

    'enable_payments_for_no_doc_merchants_experiment_id'  => env('ENABLE_PAYMENTS_FOR_NO_DOC_MERCHANTS_EXPERIMENT_ID'),

    'add_payment_acceptance_fields_to_account_v2_response' => env('ADD_PAYMENT_ACCEPTANCE_FIELDS_TO_ACCOUNT_V2_RESPONSE'),

    'enable_kyc_qualified_unactivated' => env('ENABLE_KYC_QUALIFIED_UNACTIVATED_EXP_ID'),

    'enable_document_check_for_error_code' => env('ENABLE_DOCUMENT_CHECK_FOR_ERROR_CODE_EXP_ID'),

    'enable_document_expiry_check_for_activation' => env('ENABLE_DOCUMENT_EXPIRY_CHECK_FOR_ACTIVATION_EXP_ID'),

    'add_delay_timestamp_for_kafka_event' => env('ADD_DELAY_TIMESTAMP_FOR_KAFKA_EVENT'),

    'skip_merchant_context_update_for_common_artefact' => env('SKIP_MERCHANT_CONTEXT_UPDATE_FOR_COMMON_ARTEFACT'),

    'enable_compliance_checks_on_admin_activation_workflows' => env('ENABLE_COMPLIANCE_CHECKS_ON_ADMIN_ACTIVATION_WORKFLOWS'),

    'enable_compliance_checks_on_auto_kyc_rules' => env('ENABLE_COMPLIANCE_CHECKS_ON_AUTO_KYC_RULES'),

    'vpa_validation' => env('VPA_VALIDATION'),

    'remove_ngo_business_type' => env('REMOVE_NGO_BUSINESS_TYPE'),

    'enable_unverified_email_check_for_easy_onboarding' => env('ENABLE_UNVERIFIED_EMAIL_CHECK_FOR_EASY_ONBOARDING_EXP_ID'),


    '1cc_branded_btn_splitz_exp_id' => env('MAGIC_CHECKOUT_BRANDED_BUTTON_EXP_ID'),

    'magic_shopify_taxes_admin_checkout_experiment_id'  => env('MAGIC_SHOPIFY_TAXES_ADMIN_CHECKOUT_EXP_ID'),

    'magic_enable_shopify_taxes_experiment_id'  => env('MAGIC_ENABLE_SHOPIFY_TAXES_EXP_ID'),

    'magic_qr_v2_experiment_id'  => env('MAGIC_QR_V2_EXP_ID'),

    'signatory_validations_experiment_id' => env('SIGNATORY_VALIDATIONS_EXPERIMENT_ID'),

    'artefacts_signatory_validations_experiment_id'      => env('ARTEFACTS_SIGNATORY_VALIDATIONS_EXPERIMENT_ID'),

    'one_cc_auto_submit_otp_experiment_id' => env('1CC_AUTO_SUBMIT_OTP_EXP_ID'),

    'one_cc_email_optional_on_checkout_experiment_id' => env('1CC_EMAIL_OPTIONAL_ON_CHECKOUT_EXP_ID'),

    'one_cc_email_hidden_on_checkout_experiment_id' => env('1CC_EMAIL_HIDDEN_ON_CHECKOUT_EXP_ID'),

    'one_cc_conversion_address_improvements_experiment_id' => env('1CC_CONVERSION_ADDRESS_IMPROVEMENTS_EXP_ID'),

    'emerchantpay_maf_generation_via_sqs_experiement_id' => env('EMERCHANTPAY_MAF_GENERATION_VIA_SQS_EXPERIMENT_ID'),

    'emi_via_card_screen_splitz_experiment_id' => env('EMI_VIA_CARD_SCREEN_SPLITZ_EXPERIMENT_ID'),

    'settlement_clearance_experiment_id'      => env('SETTLEMENT_CLEARANCE_EXPERIMENT_ID'),

    'eligibility_on_std_checkout_splitz_experiment_id' =>env('ELIGIBILITY_ON_STD_CHECKOUT_SPLITZ_EXPERIMENT_ID'),

    'clevertap_migration_splitz_experiment_id' => env('CLEVERTAP_MIGRATION_SPLITZ_EXPERIMENT_ID'),

    'lazypay_whitelisted_merchants_experiment_id' => env('LAZYPAY_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'hdfc_dcemi_whitelisted_mid_experiment_id'  => env('HDFC_DCEMI_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'icici_dcemi_whitelisted_mid_experiment_id'  => env('ICICI_DCEMI_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'skip_last4_for_amex_emi_payments' => env('SKIP_LAST4_FOR_AMEX_EMI_EXP_ID'),

    '1cc_be_abandoned_cart' => env('1CC_BE_ABANDONED_CART'),

    'icic_whitelisted_merchants_experiment_id' => env('ICIC_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'magic_address_sorting_experiment_id' => env('MAGIC_ADDRESS_SORTING_EXP_ID'),

    'one_cc_external_customer_address_experiment_id' => env('ONE_CC_EXTERNAL_CUSTOMER_ADDRESS_EXP_ID'),

    'policy_wizard_v2_exp_id' => env('POLICY_WIZARD_V2_EXP_ID'),

    'partner_bank_account_param_removal_exp_id' => env('PARTNER_BANK_ACCOUNT_PARAM_REMOVAL_EXP_ID'),

    'default_config_for_platform_partners_experiment_id' => env('DEFAULT_CONFIG_FOR_PLATFORM_PARTNERS_EXP_ID'),

    'magic_preferences_routing_to_checkout_service_exp_id' => env('MAGIC_PREFERENCES_ROUTING_TO_CHECKOUT_SERVICE_EXP_ID'),

    'dispute_merchant_emails_initiate_experiment_id' => env('DISPUTE_MERCHANT_EMAILS_INITIATE_EXPERIMENT_ID'),

    'url_mismatch_reply_on_ticket_experiment_id' => env('URL_MISMATCH_REPLY_ON_TICKET_EXPERIMENT_ID'),

    'transaction_isolation_for_order_experiment_id' => env('TRANSACTION_ISOLATION_FOR_ORDER_EXP_ID'),

    'sbi_sku_v2_migration_experiment_id' => env('SBI_SKU_V2_MIGRATION_SPLITZ_EXP_ID'),

    'offers_engine_dual_write_experiment_id' => env('OFFERS_ENGINE_DUAL_WRITE_EXP_ID'),

    'offers_engine_fetch_offers_exp_id' => env('OFFERS_ENGINE_FETCH_OFFERS_EXP'),

    'offers_engine_validate_offer_exp_id' => env('OFFERS_ENGINE_VALIDATE_OFFER_EXP'),

    'offers_engine_reverse_shadow_exp_id' => env('OFFERS_ENGINE_REVERSE_SHADOW_EXP'),

    'offers_engine_find_by_public_id_migration_exp_id' => env('OFFERS_ENGINE_FIND_BY_PUBLIC_ID_MIGRATION_EXP'),

    'fee_based_gating_exp_id' => env('FEE_BASED_GATING_EXP_ID'),

    'fee_based_gating_website_exp_id' => env('FEE_BASED_GATING_WEBSITE_EXP_ID'),

    'split_payment_enabled_experiment_id' => env('SPLIT_PAYMENT_ENABLED_EXPERIMENT_ID'),

    'pp_brand_color_hex' => env('PP_BRAND_COLOR_HEX'),

    'route_linked_account_2fa_exp_id' => env('ROUTE_LINKED_ACCOUNT_2FA_EXP_ID'),

    'route_microservice_nss_txn_streaming' => env('ROUTE_MICROSERVICE_NSS_TXN_STREAMING_EXP_ID'),

    'transaction_isolation_fallback_query_experiment_id' => env('TRANSACTION_ISOLATION_FALLBACK_QUERY_EXP_ID'),

    'es_search_on_created_at_then_on_score_experiment_id' => env('ES_SEARCH_ON_CREATED_AT_THEN_ON_SCORE_EXPERIMENT_ID'),

    'transaction_isolation_for_refund_experiment_id' => env('TRANSACTION_ISOLATION_FOR_REFUND_EXP_ID'),

    'transaction_isolation_for_invoice_experiment_id' => env('TRANSACTION_ISOLATION_FOR_INVOICE_EXP_ID'),

    'transaction_isolation_for_subscription_experiment_id' => env('TRANSACTION_ISOLATION_FOR_SUBSCRIPTION_EXP_ID'),

    'transaction_isolation_for_virtual_account_experiment_id' => env('TRANSACTION_ISOLATION_FOR_VIRTUAL_ACCOUNT_EXP_ID'),

    'transaction_isolation_for_qr_code_experiment_id' => env('TRANSACTION_ISOLATION_FOR_QR_CODE_EXP_ID'),

    'transaction_isolation_for_payment_experiment_id' => env('TRANSACTION_ISOLATION_FOR_PAYMENT_EXP_ID'),

    'transaction_isolation_for_transfer_experiment_id' => env('TRANSACTION_ISOLATION_FOR_TRANSFER_EXP_ID'),

    'transaction_isolation_for_dispute_experiment_id' => env('TRANSACTION_ISOLATION_FOR_DISPUTE_EXP_ID'),

    'restrict_pii_data_access_experiment_id' => env('RESTRICT_PII_DATA_ACCESS_EXP_ID'),

    'merchant_policies_subdomain' =>  env('MERCHANT_POLICIES_SUBDOMAIN'),

    'magic_poll_shipping_rates_experiment_id' => env('MAGIC_POLL_SHIPPING_RATES_EXP_ID'),

    'magic_complete_checkout_decomp_experiment_id' => env('MAGIC_COMPLETE_CHECKOUT_DECOMP_EXP_ID'),

    'magic_complete_checkout_decomp_feature_flags_experiment_id' => env('MAGIC_COMPLETE_CHECKOUT_DECOMP_FEATURE_FLAGS_EXP_ID'),

    'one_cc_customer_gstin_experiment_id' => env('ONE_CC_CUSTOMER_GSTIN_EXPERIMENT_ID'),

    'one_cc_triple_consent_experiment_id' => env('ONE_CC_TRIPLE_CONSENT_EXPERIMENT_ID'),

    'magic_complete_checkout_async_decomp_experiment_id' => env('MAGIC_COMPLETE_CHECKOUT_ASYNC_DECOMP_EXP_ID'),

    'magic_update_shipping_address_experiment_id' => env('MAGIC_UPDATE_SHIPPING_ADDRESS_EXP_ID'),

    'direct_send_mail_enabled' => env('DIRECT_SEND_MAIL_ENABLED'),

    'customer_async_transfer_experiment_id' => env('CUSTOMER_ASYNC_TRANSFER_EXPERIMENT_ID'),

    'split_payment_flow_new' => env('SPLIT_PAYMENT_FLOW_NEW_EXPERIMENT_ID'),

    'customer_transfer_reverse_shadow_v2' => env('CUSTOMER_TRANSFER_REVERSE_SHADOW_V2_EXPERIMENT_ID'),

    'customer_transfer_reverse_shadow_v2_postpaid' => env('CUSTOMER_TRANSFER_REVERSE_SHADOW_V2_POSTPAID_EXPERIMENT_ID'),

    'customer_transfer_reverse_shadow_v2_amount_credits' => env('CUSTOMER_TRANSFER_REVERSE_SHADOW_V2_AMOUNT_CREDITS_EXPERIMENT_ID'),

    'customer_transfer_reverse_shadow_v2_negative_limit' => env('CUSTOMER_TRANSFER_REVERSE_SHADOW_V2_NEGATIVE_LIMIT_EXPERIMENT_ID'),

    'enable_routes_for_pos_merchant_exp_id' => env('POS_ENABLE_ROUTES_FOR_POS_MERCHANTS_EXP_ID'),

    'freshdesk_onboarding_type_key'     => env('FRESHDESK_ONBOARDING_TYPE_KEY'),

    'email_optional_partner_MIDs' => env('EMAIL_OPTIONAL_PARTNER_MIDS'),

    'invite_merchant_with_2FA_experiment_id' => env('INVITE_MERCHANT_WITH_2FA_EXP_ID'),

    'enable_onboarding_apis_access_exp_id' => env('ENABLE_ONBOARDING_APIS_ACCESS_EXP_ID'),

    'magic_shopify_apply_coupon_decomp_experiment_id' => env('MAGIC_SHOPIFY_APPLY_COUPON_DECOMP_EXP_ID'),

    'magic_merchant_apply_coupon_decomp_experiment_id' => env('MAGIC_MERCHANT_APPLY_COUPON_DECOMP_EXP_ID'),

    'magic_shopify_remove_coupon_decomp_experiment_id' => env('MAGIC_SHOPIFY_REMOVE_COUPON_DECOMP_EXP_ID'),

    'magic_merchant_remove_coupon_decomp_experiment_id' => env('MAGIC_MERCHANT_REMOVE_COUPON_DECOMP_EXP_ID'),

    'greylisted_inclusion_for_automation' => env('GREYLISTED_INCLUSION_FOR_AUTOMATION'),

    'pgos_l2_submit' => env('PGOS_L2_SUBMIT'),

    'subcategory_exclusion_for_automation' => env('SUBCATEGORY_EXCLUSION_FOR_AUTOMATION'),

    'partnership_unblock_huf_business_type_experiment_id' => env('PARTNERSHIP_UNBLOCK_HUF_BUSINESS_TYPE_EXP_ID'),

    'submerchant_prefill_login_exp_id' => env('SUBMERCHANT_PREFILL_LOGIN_EXP_ID'),

    'phantom_prefill_contact_number_exp_id' => env('PHANTOM_PREFILL_CONTACT_NUMBER_EXP_ID'),

    'onboarding_api_upi_terminal_creation_disabled' => env('ONBOARDING_API_UPI_TERMINAL_CREATION_DISABLED'),

    'settlements_processed_comms_experiment_id' => env('SETTLEMENTS_PROCESSED_COMMS_EXPERIMENT_ID'),

    'transfer_settlement_nss_experiment_id'=> env('TRANSFER_SETTLEMENT_NSS_EXPERIMENT_ID'),

    'fee_breakup_in_ledger_experiment_id' => env('FEE_BREAKUP_IN_LEDGER_EXP_ID'),

    'amount_credits_split_in_ledger_experiment_id' => env('AMOUNT_CREDITS_SPLIT_IN_LEDGER_EXPERIMENT_ID'),

    'get_merchant_activation_response_from_pgos' => env('GET_MERCHANT_ACTIVATION_FROM_PGOS'),

    'onboarding_api_bmc_experiment_id'  => env('ONBOARDING_API_BMC_EXPERIMENT_ID'),

    'optimize_fetch_submerchants_experiment_id'  => env('OPTIMIZE_FETCH_SUBMERCHANTS_EXPERIMENT_ID'),

    'migrate_partner_increase_resources_exp_id'  => env('MIGRATE_PARTNER_INCREASE_RESOURCES_EXP_ID'),

    'cmma_subm_escalation_experiment_id' => env('CMMA_SUBM_ESCALATION_EXPERIMENT_ID'),

    'zestmoney_whitelisted_merchants_experiment_id'  => env('ZESTMONEY_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'hdfc_cardless_emi_whitelisted_merchants_experiment_id' => env('HDFC_CARDLESS_EMI_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'liquiloans_whitelisted_merchants_experiment_id' => env('LIQUILOANS_WHITELISTED_MERCHANTS_SPLITZ_EXP_ID'),

    'downtime_manager_routing_experiment' => env('DOWNTIME_MANAGER_ROUTING_SPLITZ_EXPERIMENT_ID'),

    'x_data_privacy_splitz_experiment_id' => env('X_DATA_PRIVACY_SPLITZ_EXPERIMENT_ID'),

    'recurring_customer_contact_reuse' => env('RECURRING_CUSTOMER_CONTACT_REUSE_EXP_ID'),

    'get_pan_list_for_activated_merchants_experiment_id' => env('GET_PAN_LIST_FOR_ACTIVATED_MERCHANTS_EXPERIMENT_ID'),

    'user_email_update_conflict' => env('USER_EMAIL_UPDATE_CONFLICT'),

    'user_mobile_update_conflict' => env('USER_MOBILE_UPDATE_CONFLICT'),

    'invoice_card_payment_on_rearch_splitz_experiment_id' => env('INVOICE_CARD_PAYMENT_ON_REARCH_SPLITZ_EXPERIMENT_ID'),

    'invoice_webhooks_payment_rearch_splitz_experiment_id' => env('INVOICE_WEBHOOKS_PAYMENT_REARCH_SPLITZ_EXPERIMENT_ID'),

    'storefront_card_payment_on_rearch_splitz_experiment_id' => env('STOREFRONT_CARD_PAYMENT_ON_REARCH_SPLITZ_EXPERIMENT_ID'),

    'is_kkbk_v2_emi_plans_experiment_id' => env('IS_KKBK_V2_EMI_PLANS_EMPERIMENT_ID'),

    'is_idfb_v2_emi_plans_experiment_id' => env('IS_IDFB_V2_EMI_PLANS_EMPERIMENT_ID'),

    'is_yesb_v2_emi_plans_experiment_id' => env('IS_YESB_V2_EMI_PLANS_EMPERIMENT_ID'),

    'show_dcc_markup_visa_experiment_id' => env('SHOW_DCC_MARKUP_VISA_EXPERIMENT_ID'),

    'show_dcc_markup_mc_experiment_id' => env('SHOW_DCC_MARKUP_MC_EXPERIMENT_ID'),

    'restricted_scheduled_es_migration_experiment_id' => env('RESTRICTED_SCHEDULED_ES_MIGRATION_EXP_ID'),

    'banking_mail_otp_email_verify_exp_id' => env('BANKING_MAIL_OTP_EMAIL_VERIFY_EXP_ID'),

    'banking_mail_activated_mcc_pending_action_required_exp_id' => env('BANKING_MAIL_ACTIVATED_MCC_PENDING_ACTION_REQUIRED_EXP_ID'),

    'banking_mail_password_change_exp_id' => env('BANKING_MAIL_PASSWORD_CHANGE_EXP_ID'),

    'scheduled_es_enablement_migration_experiment_id' => env('SCHEDULED_ES_ENABLEMENT_MIGRATION_EXP_ID'),

    'feature_config_from_capital_es_experiment_id' => env('FEATURE_CONFIG_FROM_CAPITAL_ES_EXP_ID'),

    'banking_mail_invite_merchant_exp_id' => env('BANKING_MAIL_INVITE_MERCHANT_EXP_ID'),

    'banking_mail_rejection_notification_exp_id' => env('BANKING_MAIL_REJECTION_NOTIFICATION_EXP_ID'),

    'banking_mail_activated_mcc_pending_success_exp_id' => env('BANKING_MAIL_ACTIVATED_MCC_PENDING_SUCCESS_EXP_ID'),

    'api_stork_banking_mail_reset_password_id' => env('STORK_BANKING_MAIL_RESET_PASSWORD_EXPERIMENT_ID'),

    'ups_unexpected_payment_experiment_id' => env('UPS_UNEXPECTED_PAYMENT_EXPERIMENT_ID'),

    'alt_id-fallback_api' => env('ALT_ID_FALLBACK_API'),

    'route_rearch_exp_id' => env('ROUTE_REARCH_EXP_ID'),

    'transfer_balance_config_balance_id_harvester_experiment' => env('TRANSFER_BALANCE_CONFIG_BALANCE_ID_EXP_ID'),

    'route_tidb_fetch_exp_id' => env('ROUTE_TIDB_FETCH_EXP_ID'),

    'order_transfer_idempotency_exp_id' => env('ORDER_TRANSFER_IDEMPOTENCY_EXP_ID'),

    'recurring_populate_error_metadata' => env('RECURRING_POPULATE_ERROR_METADATA'),

    'visa_cvv_less_experiment' => env('VISA_CVV_LESS_EXPERIMENT'),

    'transaction_created_fire_webhook_sync'=>env('TXN_CREATED_FIRE_WEBHOOK_SYNC'),

    'refund_creation_data_cls_balance_experiment' => env('REFUND_CREATION_DATA_CLS_BALANCE_EXP_ID'),

    'refund_journal_payload_harvester_balance_experiment' => env('REFUND_JOURNAL_PAYLOAD_BALANCE_EXP_ID'),

    'reversal_debit_journal_payload_harvester_balance_experiment' => env('REVERSAL_DEBIT_JOURNAL_BALANCE_EXP_ID'),

    'refund_reversal_txn_experiment' => env('REFUND_REVERSAL_TXN_FETCH_EXP_ID'),

    'validate_transfer_using_oauth_exp_id' => env('VALIDATE_TRANSFER_USING_OAUTH'),

    'gifu_upi_ds_settlement_timestamp_exp_id' => env('GIFU_UPI_DS_SETTLEMENT_TIMESTAMP_EXP_ID'),

    'pos_activation_check_for_offline_payments_splitz_exp_id' => env('POS_ACTIVATION_CHECK_FOR_OFFLINE_PAYMENTS_SPLITZ_EXP_ID'),

    'merchant_activation_pos_activation_check_for_transfers_splitz_exp_id' => env('MERCHANT_ACTIVATION_POS_ACTIVATION_CHECK_FOR_TRANSFERS_SPLITZ_EXP_ID'),

    'splitz_merchant_cls_balance_read_experiment_id'    => env('SPLITZ_MERCHANT_CLS_BALANCE_READ_EXPERIMENT_ID'),

    'splitz_harvester_query_upi_experiment_id'          => env('SPLITZ_HARVESTER_QUERY_UPI_EXPERIMENT_ID'),

    'splitz_harvester_query_core_experiment_id'         => env('SPLITZ_HARVESTER_QUERY_CORE_EXPERIMENT_ID'),

    'splitz_harvester_query_partnership_experiment_id'  => env('SPLITZ_HARVESTER_QUERY_PARTNERSHIP_EXPERIMENT_ID'),

    'splitz_merchant_acq_harvester_query_experiment_id' => env('SPLITZ_MERCHANT_ACQ_HARVESTER_QUERY_EXPERIMENT_ID'),

    'splitz_post_payment_harvester_query_experiment_id' => env('SPLITZ_POST_PAYMENT_HARVESTER_QUERY_EXPERIMENT_ID'),

    'splitz_payout_harvester_query_experiment_id'       => env('SPLITZ_PAYOUT_HARVESTER_QUERY_EXPERIMENT_ID'),

    'splitz_insufficient_balance_experiment_id'          => env('SPLITZ_INSUFFICIENT_BALANCE_EXPERIMENT_ID'),

    'qr_code_v1_failed_payment_experiment' => env('QRCODEV1_FAILED_PAYMENT_EXPERIMENT'),

    'transaction_read_experiment' => env('TRANSACTION_READ_EXPERIMENT_ID'),

    'transfer_read_experiment' => env('TRANSFER_READ_EXPERIMENT_ID'),

    'reversal_read_experiment' => env('REVERSAL_READ_EXPERIMENT_ID'),

    'ezetap_device_notification_gateway_enabled' => env('EZETAP_DEVICE_NOTIFICATION_GATEWAY_ENABLED'),

    'fulcrum_recurring_initial_experiment' => env('FULCRUM_RECURRING_INITIAL_PAYMENT_EXP_ID'),

    'mecode_sihub_from_initial' => env('MECODE_SIHUB_FROM_INITIAL'),

    'splitz_recurring_harvester_query_experiment_id' => env('SPLITZ_RECURRING_HARVESTER_QUERY_EXPERIMENT_ID'),

    'fulcrum_recurring_subsequent_experiment' => env('FULCRUM_RECURRING_SUBSEQUENT_PAYMENT_EXP_ID'),

    'bank_data_via_npci_api_experiment' => env('BANK_DATA_VIA_NPCI_API_EXP_ID'),

    'hdfc_ecms_fund_trans_experiment_id' => env('HDFC_ECMS_FUND_TRANS_EXPERIMENT_ID'),

    'merchant_checkout_optimizer_affordability_emi_enabled_exp_id' => env('MERCHANT_CHECKOUT_OPTIMIZER_AFFORDABILITY_EMI_ENABLED_EXP_ID'),

    'emit_pgos_consumer_metric_experiment' => env('EMIT_PGOS_CONSUMER_METRIC_EXPERIMENT_ID'),

    'payout_properties_event_experiment_id' => env('PAYOUT_PROPERTIES_EVENT_EXPERIMENT_ID'),

    'my_save_card_splitz_experiment_id' => env('MY_SAVE_CARD_SPLITZ_EXPERIMENT_ID'),

    'p2p' => [
        'encryption_key' => env('P2P_NACL_SESSION_TOKEN_ENCRYPTION_KEY')
    ],

    'dcs_edit_enabled_splitz_exp_id' => env('DCS_EDIT_ENABLED_SPLITZ_EXP_ID'),

    'dcs_proxy_enabled_splitz_exp_id' => env('DCS_PROXY_ENABLED_SPLITZ_EXP_ID'),

    'external_updates_enabled_for_tokens' => env('EXTERNAL_UPDATES_ENABLED_FOR_TOKENS'),

    'pg_pos_rbac_splitz_experiment_id' => env('PG_POS_RBAC_SPLITZ_EXPERIMENT_ID'),

    'pg_pos_auth_rbac_splitz_experiment_id' => env('PG_POS_AUTH_RBAC_SPLITZ_EXPERIMENT_ID'),

    'insufficient_fund_tng' => env('INSUFFICIENT_FUND_TNG'),

    'generate_bene_hash_experiment_id' => env('GENERATE_BENE_HASH_EXPERIMENT_ID'),

    'cms_create_override_test_experiment_id' => env('CMS_CREATE_OVERRIDE_TEST_EXPERIMENT_ID'),

    'cms_create_override_live_experiment_id' => env('CMS_CREATE_OVERRIDE_LIVE_EXPERIMENT_ID'),

    'stop_async_capture_card_gateways' => env('STOP_ASYNC_CAPTURE_CARD_GATEWAYS'),

    'skip_optimizer_card_callback' => env('SKIP_OPTIMIZER_CARD_CALLBACK'),

    'handle_async_balance_update_by_redis_queue_exp_id' => env('HANDLE_ASYNC_BALANCE_UPDATE_BY_REDIS_QUEUE_EXP_ID'),

    'enable_feature_fetch_from_dcs_exp_id' => env('ENABLE_FEATURE_FETCH_FROM_DCS_EXP_ID'),

    'gifu_card_upi_ds_refunds_exp_id' => env('GIFU_CARD_UPI_DS_REFUNDS_EXP_ID')

);
