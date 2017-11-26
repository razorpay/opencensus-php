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
            \RZP\Http\Middleware\EventTracker::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \RZP\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            // 'throttle:60,1',
            \RZP\Http\Middleware\EventTracker::class
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'route'        => \RZP\Http\Middleware\Route::class,
        'throttle'     => \RZP\Http\Middleware\Throttle::class,
        'auth'         => \RZP\Http\Middleware\Authenticate::class,
        'admin_access' => \RZP\Http\Middleware\AdminAccess::class,
        'workflow'     => \RZP\Http\Middleware\Workflow::class,
    ];
}
