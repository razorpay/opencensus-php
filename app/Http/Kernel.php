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
        'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
        'Illuminate\Cookie\Middleware\EncryptCookies',
        'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
        'App\Http\Middleware\TrustedProxy',
        'Illuminate\Session\Middleware\StartSession',
        'Illuminate\View\Middleware\ShareErrorsFromSession',
        'App\Http\Middleware\VerifyCsrfToken',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'          => 'App\Http\Middleware\Authenticate',
        'auth.basic'    => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        'auth.internal' => 'App\Http\Middleware\InternalAuth',
        'auth.cron'     => 'App\Http\Middleware\CronAuth',
        'guest'         => 'App\Http\Middleware\RedirectIfAuthenticated',
        'admin'         => 'App\Http\Middleware\AuthenticateAdmin',
        'superadmin'    => 'App\Http\Middleware\AuthenticateSuperAdmin',
        'slack'         => 'App\Http\Middleware\Slack',
    ];

}
