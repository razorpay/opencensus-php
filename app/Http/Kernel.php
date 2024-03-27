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
        \App\Http\Middleware\Metrics::class,
        \App\Http\Middleware\SetResponseLogHeaders::class,
    ];

    /**
      * The application's route middleware groups.
      *
      * @var array
      */
    protected $middlewareGroups = [
        'web' => [
            \App\Edge\Middleware\EdgeResponseHandler::class,
            \App\Http\Middleware\Cors::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Edge\Middleware\EdgeRequestHandler::class,
            \App\Http\Middleware\SetApiHeaders::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SessionInActivity::class,
            \App\Http\Middleware\CacheControl::class,
            \App\Http\Middleware\OTPVerificationSession::class,
        ],
        'jwt_session' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\CacheControl::class,
        ],

        'graph'     => [
            \App\Http\Middleware\Cors::class,
            \App\Http\Middleware\EncryptCookies::class,
            \App\Http\Middleware\StartSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\GraphRequestAuthCheckV2::class,
            \App\Http\Middleware\CacheControl::class,
        ],

        'graph_oauth' => [
            \App\Http\Middleware\Cors::class,
            \App\Http\Middleware\AuthenticateOauth::class,
            \App\Http\Middleware\GraphRequestAuthCheck::class,
            \App\Http\Middleware\CacheControl::class,
        ],

        'web_oauth' => [
            \App\Http\Middleware\Cors::class,
            \App\Http\Middleware\AuthenticateOauth::class,
            \App\Http\Middleware\CacheControl::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'           => 'App\Http\Middleware\Authenticate',
        'auth.basic'     => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        'auth.internal'  => 'App\Http\Middleware\InternalAuth',
        'auth.graph'     => 'App\Http\Middleware\GraphAuth',
        'auth.cron'      => 'App\Http\Middleware\CronAuth',
        'guest'          => 'App\Http\Middleware\RedirectIfAuthenticated',
        'admin'          => 'App\Http\Middleware\AuthenticateAdmin',
        'superadmin'     => 'App\Http\Middleware\AuthenticateSuperAdmin',
        'slack'          => 'App\Http\Middleware\Slack',
        'admin_access'   => 'App\Http\Middleware\AdminAccess',
        'verified'       => 'App\Http\Middleware\Verified',
        'jwt'            => 'App\Http\Middleware\JWTValidate',
        'auth.oauth'     => 'App\Http\Middleware\OAuth',
        'guest.generic'  => 'App\Http\Middleware\GuestGeneric',
        'set_x_frame'    => 'App\Http\Middleware\SetXFrameOptionsHeader',
        'set_csp_header' => 'App\Http\Middleware\SetCspHeader',
        'tnc_popup'      => 'App\Http\Middleware\TermsAndConditionsPopUp',
    ];

}
