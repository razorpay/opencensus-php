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
        Illuminate\Session\SessionServiceProvider::class,
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
        \RZP\Providers\SqsRawServiceProvider::class
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

    'cdn_v1_url'                          => env('CDN_V1_URL'),

    'proxy_enabled'                       => env('PROXY_ENABLED'),

    'proxy_address'                       => env('PROXY_ADDRESS'),

    'subscription_proxy_timeout'          => env('SUBSCRIPTION_PROXY_TIMEOUT', 10),

    'offline_verification_proxy_timeout'  => env('OFFLINE_VERIFICATION_PROXY_TIMEOUT', 10),

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

    'admin_submerchant_bulk_increase_resources_exp_id' => env('ADMIN_SUBMERCHANT_BULK_INCREASE_RESOURCES_EXP_ID'),

    'partner_add_submerchant_account_exp_id'    => env('PARTNER_ADD_SUBMERCHANT_ACCOUNT_EXP_ID'),

    '1cc_splitz_experiment_id' => env('MAGIC_CHECKOUT_SPLITZ_EXPERIMENT_ID'),

    '1cc_city_autopopulate_splitz_experiment_id' => env('MAGIC_CHECKOUT_DISABLE_AUTOPOPULATE_EXP_ID'),

    '1cc_cart_items_splitz_experiment_id' => env('MAGIC_CHECKOUT_CART_ITEMS_EXP_ID'),

    'shopify_1cc_sqs_splitz_experiment_id' => env('SHOPIFY_1CC_SQS_SPLITZ_EXPERIMENT_ID'),

    'global_card_payment_splitz_experiment_id' => env('GLOBAL_CARD_PAYMENT_SPLITZ_EXPERIMENT_ID'),

    'local_token_on_global_customer_experiment_id' => env('LOCAL_TOKEN_ON_GLOBAL_CUSTOMER_EXPERIMENT_ID'),

    'payment_handle_domain' => env('PAYMENT_HANDLE_DOMAIN'),

    'nocode' => [
        'cache' => [
            'slug_ttl'      => env('NOCODE_SLUG_CACHE_TTL', 86400),
            'prefix'        => env('NOCODE_CACHE_PREFIX', 'NOCODE'),
            'hosted_ttl'    => env('NOCODE_HOSTED_CACHE_TTL', 3600),
            'custom_url_ttl'    => env('NOCODE_CUSTOM_URL_CACHE_TTL', 86400),
        ]
    ],

    'db_migration_metrics_sampling_percent' => env('DB_MIGRATION_METRIC_SAMPLING_PERCENT', 1.0),

    'product_config_issue_exp_id' => env('PRODUCT_CONFIG_ISSUE_EXP_ID'),
);
