<?php

namespace RZP\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

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
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
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
        Middleware\Throttle::class,
        Middleware\Authenticate::class,
        Middleware\AdminAccess::class,
        Middleware\UserAccess::class,
        Middleware\SubscriptionProxy::class,
        Middleware\ExcelStoreProxy::class,
        Middleware\FailureEventsInterceptor::class,
        Middleware\Workflow::class,
        Middleware\MerchantIpFilter::class,
        Middleware\EventTracker::class,
        Middleware\P2p::class,
        Middleware\IdempotentHandler::class,

        // Route group middleware
        Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        Middleware\TemporaryStartSession::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'throttle'            => Middleware\Throttle::class,
        'auth'                => Middleware\Authenticate::class,
        'admin_access'        => Middleware\AdminAccess::class,
        'user_access'         => Middleware\UserAccess::class,
        'subscription_proxy'  => Middleware\SubscriptionProxy::class,
        'excel_store_proxy'   => Middleware\ExcelStoreProxy::class,
        'workflow'            => Middleware\Workflow::class,
        'merchant_ip_filter'  => Middleware\MerchantIpFilter::class,
        'event_tracker'       => Middleware\EventTracker::class,
        'p2p'                 => Middleware\P2p::class,
        'idempotent'          => Middleware\IdempotentHandler::class,
        'failure_interceptor' => Middleware\FailureEventsInterceptor::class,
    ];
}
