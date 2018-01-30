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
            \RZP\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
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
        // throttle: old implementation not to be used, to remove later
        // 'throttle'           => \RZP\Http\Middleware\Throttle::class,
        'throttle_v2'        => \RZP\Http\Middleware\ThrottleV2::class,
        'auth'               => \RZP\Http\Middleware\Authenticate::class,
        'admin_access'       => \RZP\Http\Middleware\AdminAccess::class,
        'user_access'        => \RZP\Http\Middleware\UserAccess::class,
        'workflow'           => \RZP\Http\Middleware\Workflow::class,
        'merchant_ip_filter' => \RZP\Http\Middleware\MerchantIpFilter::class,
        'event_tracker'      => \RZP\Http\Middleware\EventTracker::class,
    ];
}
