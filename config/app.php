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

    'razorpay_support_page_url'           => env('RAZORPAY_SUPPORT_PAGE_WEBSITE_URL'),

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
        env('PAYMENT_PAGE_AXIS_HOSTED_BASE_URL'),
        env('PAYMENT_HANDLE_HOSTED_BASE_URL'),
    ],

    'payment_store_allowed_cors_url' => [
        env('PAYMENT_STORE_HOSTED_BASE_URL'),
    ],

    'payment_handle' => [
        'secret' => env('PAYMENT_HANDLE_AMOUNT_SECRET')
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

    'send_commission_invoice_reminders_exp_id' => env('SEND_COMMISSION_INVOICE_REMINDERS_EXP_ID'),

    'partner_regenerate_referrals_links_exp_id' => env('PARTNER_REGENERATE_REFERRAL_LINKS_EXP_ID'),

    'partner_config_auditing_experiment_id' => env('PARTNER_CONFIG_AUDITING_EXPERIMENT_ID'),

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

    'wda_migration_acquisition_splitz_exp_id' => env('WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID'),

    'hybrid_data_querying_splitz_experiment_id'=> env('HYBRID_DATA_QUERYING_SPLITZ_EXPERIMENT_ID'),

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


    'generic_ai_enabled_experiment_id' => env('GENERIC_AI_ENABLED_EXPERIMENT_ID'),

    'generic_ai_enabled_experiment_result_mock' => env('GENERIC_AI_ENABLED_EXPERIMENT_RESULT_MOCK'),

    'cmma_metro_migrate_out_experiment_id' => env('CMMA_METRO_MIGRATE_OUT_EXPERIMENT_ID'),

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

    'checkout_service_preferences_splitz_experiment_id' => env('CHECKOUT_SERVICE_PREFERENCES_SPLITZ_EXPERIMENT_ID'),

    'checkout_enable_otp_auto_read_and_auto_submit_splitz_experiment_id' => env('CHECKOUT_ENABLE_OTP_AUTO_READ_AND_AUTO_SUBMIT_SPLITZ_EXPERIMENT_ID'),

    'send_submerchant_first_transaction_segment_event' => env('SEND_SUBMERCHANT_FIRST_TRANSACTION_SEGMENT_EVENT'),

    'dcc_recurring_on_auto_direct_experiment_id' => env('DCC_RECURRING_ON_AUTO_DIRECT_EXPERIMENT_ID'),

    'dcc_recurring_on_auto_direct_experiment_id' => env('DCC_RECURRING_ON_AUTO_DIRECT_EXPERIMENT_ID'),

    'cc_on_upi_pricing_splitz_experiment_id' => env('CC_ON_UPI_PRICING_SPLITZ_EXPERIMENT_ID'),

    'dcc_on_auto_subscription_payments_experiment_id' => env('DCC_ON_AUTO_SUBSCRIPTION_PAYMENTS_EXPERIMENT_ID'),

    'partner_independent_kyc_exp_id' => env('PARTNER_INDEPENDENT_KYC_EXP_ID'),

    'optimise_submerchant_create_exp_id' => env('OPTIMISE_SUBMERCHANT_CREATE_EXP_ID'),

    'route_partnership_v1_guards_exp_id' => env('ROUTE_PARTNERSHIP_V1_GUARD_EXP_ID'),

    'skip_avs_on_3ds_experiment_id' => env('SKIP_AVS_CHECK_ON_3DS_EXPERIMENT_ID'),

    '1cc_coupon_drop_off_splitz_experiment_id' => env('MAGIC_CHECKOUT_COUPONS_DROP_OFF_EXP_ID'),

    'partnership_service_commission_sync_exp_id' => env('PARTNERSHIP_SERVICE_COMMISSION_SYNC_EXP_ID'),

    'partnership_service_commission_shadow_phase_exp_id' => env('PARTNERSHIP_SERVICE_COMMISSION_SHADOW_PHASE_EXP_ID'),

    'commission_invoice_events_to_kafka_exp_id'  => env('COMMISSION_INVOICE_EVENTS_TO_KAFKA_EXP_ID'),

    'magic_checkout_woocommerce_giftcard_url'       => env('MAGIC_CHECKOUT_WOOCOMMERCE_GIFTCARD_URL'),

    '1cc_multiple_shipping_splitz_experiment_id' => env('MAGIC_CHECKOUT_MULTIPLE_SHIPPING_EXP_ID'),

    'partner_submerchant_whitelabel_onboarding' => env('PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING'),

    'partner_submerchant_oauth_onboarding' => env('PARTNER_SUBMERCHANT_OAUTH_ONBOARDING'),

    'magic_apply_coupon_experiment_id' => env('MAGIC_APPLY_COUPON_EXPERIMENT_ID'),

    'capital_partnership_experiment_id' => env('CAPITAL_PARTNERSHIP_EXPERIMENT_ID'),

    'capital_partner_new_referral_link_experiment_id' => env('CAPITAL_PARTNER_NEW_REFERRAL_LINK_EXPERIMENT_ID'),

    '1cc_enable_v165_splitz_experiment_id' => env('MAGIC_CHECKOUT_ENABLE_V165_EXP_ID'),

    '1cc_coupons_with_se_splitz_experiment_id' => env('MAGIC_CHECKOUT_COUPONS_WITH_SCRIPT_EDITOR_EXP_ID'),

    '1cc_order_default_pending_splitz_experiment_id' => env('MAGIC_CHECKOUT_ORDER_WITH_PENDING_STATUS_EXP_ID'),

    '1cc_allow_partial_refund_splitz_experiment_id' => env('MAGIC_CHECKOUT_ALLOW_PARTIAL_REFUND_EXP_ID'),

    'magic_offers_fix_splitz_experiment_id'  => env('MAGIC_CHECKOUT_OFFERS_FIX_EXP_ID'),

    'vendor_payment_via_corp_card_experiment_id' => env('VENDOR_PAYMENT_VIA_CORP_CARD_EXPERIMENT_ID'),

    '1cc_shipping_info_migration_splitz_experiment_id' => env('MAGIC_CHECKOUT_SHIPPING_INFO_MIGRATION_EXP_ID'),

    'magic_show_coupon_callout_experiment_id'  => env('MAGIC_SHOW_COUPON_CALLOUT_EXP_ID'),

    'merchant_automation_activation_exp_id' => env('MERCHANT_AUTOMATION_ACTIVATION_EXP_ID'),

    'pgos_migration_dual_writing_exp_id' => env('PGOS_MIGRATION_DUAL_WRITING_EXP_ID'),

    'enable_payments_for_no_doc_merchants_experiment_id'  => env('ENABLE_PAYMENTS_FOR_NO_DOC_MERCHANTS_EXPERIMENT_ID'),

    'add_payment_acceptance_fields_to_account_v2_response' => env('ADD_PAYMENT_ACCEPTANCE_FIELDS_TO_ACCOUNT_V2_RESPONSE'),

    'enable_kyc_qualified_unactivated' => env('ENABLE_KYC_QUALIFIED_UNACTIVATED_EXP_ID'),

    '1cc_branded_btn_splitz_exp_id' => env('MAGIC_CHECKOUT_BRANDED_BUTTON_EXP_ID'),

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
);
