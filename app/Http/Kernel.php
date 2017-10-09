<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\TrustedProxy::class,
    ];

    /**
      * The application's route middleware groups.
      *
      * @var array
      */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SetApiHeaders::class,
        ]
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'cors'           => 'App\Http\Middleware\Cors',
        'auth'           => 'App\Http\Middleware\Authenticate',
        'auth.basic'     => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        'auth.internal'  => 'App\Http\Middleware\InternalAuth',
        'auth.cron'      => 'App\Http\Middleware\CronAuth',
        'guest'          => 'App\Http\Middleware\RedirectIfAuthenticated',
        'admin'          => 'App\Http\Middleware\AuthenticateAdmin',
        'superadmin'     => 'App\Http\Middleware\AuthenticateSuperAdmin',
        'slack'          => 'App\Http\Middleware\Slack',
        'admin_access'   => 'App\Http\Middleware\AdminAccess',
        'verified'       => 'App\Http\Middleware\Verified',
        'auth.oauth'     => 'App\Http\Middleware\OAuth',
        'generic.noauth' => 'App\Http\Middleware\GenericNoAuth',
    ];

}
