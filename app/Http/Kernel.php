<?php

namespace RZP\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

use RZP\Modules\Acs;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     * Removed \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode cause it's useless.
     *
     * @var array
     */
    protected $middleware = [
        Middleware\InspectorAccess::class,
        Middleware\TrustedProxy::class,
        Middleware\VerifyHttps::class,
        Acs\SyncEventTriggerMiddleware::class,
        Middleware\StripQueryParam::class,
        Middleware\StripReferrerParam::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'sessionMinimumLifetime' => [
            Middleware\SessionMinimumLifetime::class,
        ],
        'web' => [
            Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Middleware\SameSiteSession::class,
            Middleware\StartSession::class,
            Middleware\TemporaryStartSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \RZP\Http\Middleware\VerifyCsrfToken::class,
        ],
    ];

    /**
     * {@inheritDoc}
     *
     * Defines the order of middleware execution. If a middleware is undefined here, it gets
     * added at the end.
     *
     * @var array
     */
    protected $middlewarePriority = [
        // Route middleware
        Middleware\ErrorHandlerSetterForPHPLaravelUpgrade::class,
        Middleware\SaveApiDetailsForDocumentation::class,
        Middleware\AddDashboardResponseHeaders::class,
        Middleware\DecodePassportJwt::class,
        Middleware\ProductIdentifier::class,
        Middleware\ProxySQL::class,
        Middleware\Throttle::class,
        Middleware\Authenticate::class,
        Middleware\SDKMetric::class,
        Middleware\AdminAccess::class,
        Middleware\UserAccess::class,
        Middleware\SubscriptionProxy::class,
        Middleware\ExcelStoreProxy::class,
        Middleware\FailureEventsInterceptor::class,
        Middleware\Workflow::class,
        Middleware\MerchantIpFilter::class,
        Middleware\EventTracker::class,
        Middleware\P2p::class,
        Middleware\MerchantIdempotencyHandler::class,
        Middleware\RequestContextHandler::class,
        Acs\SyncEventTriggerMiddleware::class,

        // Route group middleware
        Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        Middleware\SameSiteSession::class,
        \Illuminate\Session\Middleware\StartSession::class,
        Middleware\TemporaryStartSession::class,
        Middleware\RequestLogHandler::class,
        Middleware\UserRoleBasedResponseFilter::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'passport_jwt'                  => Middleware\DecodePassportJwt::class,
        'product_identifier'            => Middleware\ProductIdentifier::class,
        'proxysql'                      => Middleware\ProxySQL::class,
        'throttle'                      => Middleware\Throttle::class,
        'save_api_details'              => Middleware\SaveApiDetailsForDocumentation::class,
        'auth'                          => Middleware\Authenticate::class,
        'sdk_metric'                    => Middleware\SDKMetric::class,
        'admin_access'                  => Middleware\AdminAccess::class,
        'user_access'                   => Middleware\UserAccess::class,
        'subscription_proxy'            => Middleware\SubscriptionProxy::class,
        'excel_store_proxy'             => Middleware\ExcelStoreProxy::class,
        'workflow'                      => Middleware\Workflow::class,
        'merchant_ip_filter'            => Middleware\MerchantIpFilter::class,
        'filter_response_fields'        => Middleware\UserRoleBasedResponseFilter::class,
        'event_tracker'                 => Middleware\EventTracker::class,
        'p2p'                           => Middleware\P2p::class,
        'merchant_idempotency_handler'  => Middleware\MerchantIdempotencyHandler::class,
        'failure_interceptor'           => Middleware\FailureEventsInterceptor::class,
        'request_context'               => Middleware\RequestContextHandler::class,
        'request_log_handler'           => Middleware\RequestLogHandler::class,
        'dashboard_response_headers'    => Middleware\AddDashboardResponseHeaders::class,
        'error_handler_setter_for_php_laravel_upgrade' => Middleware\ErrorHandlerSetterForPHPLaravelUpgrade::class,
    ];
}
