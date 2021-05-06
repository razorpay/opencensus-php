<?php

namespace App\Http\Middleware;

use Auth;
use Session;
use Closure;
use App\Admin;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\Admin\ApiRequestAny;
use App\User\Constants as UserConstants;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Crypt;


class SessionInActivity
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    protected $cache;

    protected $trace;

    // org feature flag for admin dashboard logout on inactivity
    const LOGOUT_ADMIN_INACTIVITY = 'logout_admin_inactivity';

    const CACHE_STORE_TIMEOUT_FOR_ORG_FEATURES = 10; // 10 minutes

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;

        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->cache = $app['cache'];

        $this->trace = $app['trace'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($this->isAdminUserAndOrgFeatureEnabledForLogout() === false))
        {
            return $next($request);
        }

        // Meta data is stored in session with the key _sf2_meta with keys c,u,l as keys (created, updated, lifetime)
        $metaDataBag = Session::getMetadataBag();

        $user = Auth::guard('api');

        $lastUsed = $metaDataBag->getLastUsed();

        $sessionConfig = $this->app['config']['session'];

        $inActivityTime = $sessionConfig['inactivity_time_admin_dashboard'] * 60;

        $currentTime = time();

        $routeName = $request->route()->getName();

        if ((empty($user->user()) === false) and (empty($lastUsed) === false) and
            (($currentTime - $lastUsed) > $inActivityTime))
        {
            $userEmail = $user->user()->email ?? '';

            $this->trace->info(TraceCode::ADMIN_LOGOUT_ON_INACTIVITY, [
                'current_time' => $currentTime,
                'last_used'    => $lastUsed,
                'user_email'   => $userEmail,
                'org_id'       => $user->user()->org_id,
            ]);

            (new Admin\Service)->logout();

            $path = '/admin';

            if (empty($userEmail) === false)
            {
                $path .= '?email=' . $userEmail;
            }

            $response = AppResponse::unauthorizedResponse('Unauthorized.', $routeName, $path);

            $response->withCookie(cookie('rzp_user_email', Crypt::encrypt($userEmail), $sessionConfig['lifetime']));

            return $response;
        }

        return $next($request);
    }

    protected function isAdminUserAndOrgFeatureEnabledForLogout()
    {
        $user = Auth::guard('user');

        // check if merchant user is not logged in and admin user logged in
        // and admin org has feature logout_admin_inactivity
        if ((empty($user->user()) === true) and
            (Auth::guard('api')->check() === true) and
            ($this->isFeatureEnabledForOrg(self::LOGOUT_ADMIN_INACTIVITY) === true))
        {
           return true;
        }

        return false;
    }

    protected function isFeatureEnabledForOrg($featureName)
    {
        $features = (new Admin\Service)->getOrgFeatures();

        return in_array($featureName, $features);
    }
}

