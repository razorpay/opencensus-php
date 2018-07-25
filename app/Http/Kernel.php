<?php

namespace RZP\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        Middleware\InspectorAccess::class,
        Middleware\TrustedProxy::class,
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
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
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'throttle'           => Middleware\Throttle::class,
        'auth'               => Middleware\Authenticate::class,
        'admin_access'       => Middleware\AdminAccess::class,
        'user_access'        => Middleware\UserAccess::class,
        'workflow'           => Middleware\Workflow::class,
        'merchant_ip_filter' => Middleware\MerchantIpFilter::class,
        'event_tracker'      => Middleware\EventTracker::class,
    ];
}
