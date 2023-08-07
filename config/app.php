<?php

$cdn_dashboard_url = env('CDN_DASHBOARD_URL');

// Add canary inside dashboard CDN URL if instance type is `canary`
// TODO: add support for canary for non prod envs then we can remove `production` check
if (env('APP_ENV') === 'production' &&  env('INSTANCE_TYPE') === 'canary')
{
    $cdn_dashboard_url = $cdn_dashboard_url . '/canary';
}

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

    'url' => env('BASE_URL', 'https://dashboard.razorpay.com'),

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

    'cipher' => env('ENCRYPTION_CIPHER', 'AES-256-CBC'),

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

    'key' => env('APP_KEY', 'xOlhfx6IBUR2FDulBrueOHzlUCEbJ8oT'),

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

    'log' => 'daily',

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
        Illuminate\Foundation\Providers\ArtisanServiceProvider::class,
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        App\Providers\CustomSessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        App\Providers\OAuthServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AuthServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\GoogleOauthMockServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Trace\TraceServiceProvider::class,

        // This one is our own custom provider
        App\Providers\UuidServiceProvider::class,
        App\Providers\OpenCensusProvider::class,
        // We are extending because 1.4 is the last version
        // that works with L5.0
        // and it does not work with PHP7
        App\Providers\PasswordStrengthServiceProvider::class,

        App\Providers\RequestOauthServiceProvider::class,

        // Package providers follow
        Aws\Laravel\AwsServiceProvider::class,
        'Barryvdh\Debugbar\ServiceProvider',
        'Maatwebsite\Excel\ExcelServiceProvider',
        Razorpay\Slack\Laravel\ServiceProviderLaravel5::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Razorpay\Metrics\ServiceProvider::class,
        Razorpay\Trace\ServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Provider Manifest
    |--------------------------------------------------------------------------
    |
    | The service provider manifest is used by Laravel to lazy load service
    | providers which are not needed for each request, as well to keep a
    | list of all of the services. Here, you may set its storage spot.
    |
    */

    'manifest' => storage_path() . '/meta',

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

    'aliases' => array(

        'App'             => Illuminate\Support\Facades\App::class,
        'Artisan'         => Illuminate\Support\Facades\Artisan::class,
        'Auth'            => Illuminate\Support\Facades\Auth::class,
        'Blade'           => Illuminate\Support\Facades\Blade::class,
        'Bus'             => Illuminate\Support\Facades\Bus::class,
        'Cache'           => Illuminate\Support\Facades\Cache::class,
        'Config'          => Illuminate\Support\Facades\Config::class,
        'Cookie'          => Illuminate\Support\Facades\Cookie::class,
        'Crypt'           => Illuminate\Support\Facades\Crypt::class,
        'DB'              => Illuminate\Support\Facades\DB::class,
        'Eloquent'        => Illuminate\Database\Eloquent\Model::class,
        'Event'           => Illuminate\Support\Facades\Event::class,
        'File'            => Illuminate\Support\Facades\File::class,
        'Gate'            => Illuminate\Support\Facades\Gate::class,
        'Hash'            => Illuminate\Support\Facades\Hash::class,
        'Input'           => Illuminate\Support\Facades\Request::class,
        'Inspiring'       => Illuminate\Foundation\Inspiring::class,
        'Lang'            => Illuminate\Support\Facades\Lang::class,
        'Log'             => Illuminate\Support\Facades\Log::class,
        'Mail'            => Illuminate\Support\Facades\Mail::class,
        'Password'        => Illuminate\Support\Facades\Password::class,
        'Queue'           => Illuminate\Support\Facades\Queue::class,
        'Redirect'        => Illuminate\Support\Facades\Redirect::class,
        'Redis'           => Illuminate\Support\Facades\Redis::class,
        'Request'         => Illuminate\Support\Facades\Request::class,
        'Response'        => Illuminate\Support\Facades\Response::class,
        'Route'           => Illuminate\Support\Facades\Route::class,
        'Schema'          => Illuminate\Support\Facades\Schema::class,
        'Session'         => Illuminate\Support\Facades\Session::class,
        'Storage'         => Illuminate\Support\Facades\Storage::class,
        'URL'             => Illuminate\Support\Facades\URL::class,
        'Validator'       => Illuminate\Support\Facades\Validator::class,
        'View'            => Illuminate\Support\Facades\View::class,

        'AWS'             => Aws\Laravel\AwsFacade::class,
        'Debugbar'        => Barryvdh\Debugbar\Facade::class,
        'Excel'           => Maatwebsite\Excel\Facades\Excel::class,

        // Don't name it OAuth (http://php.net/manual/en/book.oauth.php)
        'OAuthFacade'     => Artdarek\OAuth\Facade\OAuth::class,
        'Slack'           => Razorpay\Slack\Laravel\Facade::class,
        'Trace'           => Razorpay\Trace\Facades\Trace::class,
        'Uuid'            => App\Facades\Uuid::class,
        'Metrics'         => Razorpay\Metrics\Facade::class,
    ),

    'cdn_dashboard_url'            => $cdn_dashboard_url,
    'cdn_base_url'                 => env('CDN_BASE_URL'),
    'lj_key'                       => env('LJ_KEY'),
    'banking_service_url'          => env('BANKING_SERVICE_URL'),
    'bank_lms_banking_service_url' => env('BANK_LMS_BANKING_SERVICE_URL', 'https://partner-lms.razorpay.com'),
    'campaignhq_url'               => env('CAMPAIGNHQ_URL'),
    'docs_url'                     => env('DOCS_URL'),
    'banking_demo_user_password'   => env('BANKING_DEMO_USER_PASSWORD'),

    'passport_public_key'        => str_replace('\n', PHP_EOL, env('PASSPORT_PUBLIC_KEY')),

    'ezetap_base_url'     => env('EZETAP_BASE_URL'),

    'rzp_website_url'     => env('RZP_WEBSITE_URL'),
    'next_rzp_url'        => env('NEXT_WEBSITE_URL'),
    'static_web_url'      => env('STATIC_WEBSITE_URL'),
    'easy_onboarding_url' => env('EASY_ONBOARDING_URL'),
    'pp_ecommerce_url' => env('PP_ECOMMERCE_URL'),
    'easy_dashboard_url'  => env('EASY_DASHBOARD_URL'),
    'is_api_circuit_breaker_enabled' =>  env('IS_API_CIRCUIT_BREAKER_ENABLED'),
    'cache_ttl_org_time_minute' => env('CACHE_TTL_ORG_TIME_MINUTE'),
);
