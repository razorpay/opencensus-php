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

    // value stored in this session key contains the last used at timestamp
    // its used to decided the duration for which this session is inactive
    const SESSION_LAST_USED_AT = 'session_last_used_at';

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
        $skip = false;

        if ($this->isMerchantDashboardLoggedIn() === true)
        {
            $skip = true;

            $this->trace->info(TraceCode::APP_NAME, [
                'isMerchantDashboardLoggedIn' => $skip
            ]);
        }

        if (($this->isAdminUserAndOrgFeatureEnabledForLogout() === false) and
              $skip === false)
        {
            return $next($request);
        }

        // Meta data is stored in session with the key _sf2_meta with keys c,u,l as keys (created, updated, lifetime)
        $user = Auth::guard('api');

        if ($skip === true)
        {
            $user = Auth::guard('api') ?? Auth::guard('user');
        }

        $lastUsed = $this->getSessionLastUsedAt();

        $sessionConfig = $this->app['config']['session'];

        $merchantInactivityTime = $this->getMerchantSessionTimeout();

        $adminInActivityTime = $sessionConfig['inactivity_time_admin_dashboard'] * 60;

        $currentTime = time();

        $routeName = $request->route()->getName();

        $isMerchantOrAdminAsMerchant = (empty(Session::get('current_merchant_id')) === false);

        if (
            $isMerchantOrAdminAsMerchant === true and
            empty($lastUsed) === false and
            (($currentTime - $lastUsed) > $merchantInactivityTime)
        )
        {
            $userEmail = $user->user()->email ?? '';

            $this->trace->info(TraceCode::MERCHANT_LOGOUT_ON_INACTIVITY, [
                'current_time' => $currentTime,
                'last_used'    => $lastUsed,
                'user_email'   => $userEmail,
            ]);

            $user->logout();

            $response = AppResponse::unauthorizedResponse('Unauthorized.', $routeName);

            return $response;

        }
        if ((empty($user->user()) === false) and (empty($lastUsed) === false) and
            (($currentTime - $lastUsed) > $adminInActivityTime))
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

        $response = $next($request);

        $this->updateSessionLastUsedAt();

        return $response;
    }

    protected function getMerchantSessionTimeout()
    {
        $sessionConfig = $this->app['config']['session'];

        $merchantInactivityTimeout = $sessionConfig['lifetime'] * 60;

        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = (new Admin\Service)->getOrg($domain, true);

        if (empty($org['merchant_session_timeout_in_seconds']) === false)
        {
            $merchantInactivityTimeout = $org['merchant_session_timeout_in_seconds'];
        }

        $this->trace->info(TraceCode::TIMEOUT_VALUE, [
            'Timeout Value' => $merchantInactivityTimeout
        ]);

        return $merchantInactivityTimeout;
    }

    protected function isMerchantDashboardLoggedIn(): bool
    {
        $user = Auth::guard('user');

        //Checking if both merchant id and user id is present
        //hence app is merchant dashboard
        //also adding an extra check on org level flag

        if ((Session::get('current_merchant_id') !== null) and
            (empty($user->user()) === false) and
            (isset($user->user()->id) === true) and
            ($this->isFeatureEnabledForOrg(self::LOGOUT_ADMIN_INACTIVITY) === true))
        {
            return true;
        }

        return false;
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

    protected function getSessionLastUsedAt()
    {
        $lastUsedAtFromSession = Session::get(self::SESSION_LAST_USED_AT);

        $this->trace->info(TraceCode::SESSION_LAST_USED_AT, [
            'session'        => $lastUsedAtFromSession,
        ]);

        return $lastUsedAtFromSession;
    }

    protected function updateSessionLastUsedAt()
    {
        Session::put(self::SESSION_LAST_USED_AT, time());
    }
}

