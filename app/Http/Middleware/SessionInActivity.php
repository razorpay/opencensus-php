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
        // Meta data is stored in session with the key _sf2_meta with keys c,u,l as keys (created, updated, lifetime)
        $metaDataBag = Session::getMetadataBag();

        $user = Auth::guard('user');

        $isAdminUser = false;

        $lastUsed = $metaDataBag->getLastUsed();

        $sessionConfig = $this->app['config']['session'];

        $inActivityTime = $sessionConfig['inactivity_time'] * 60;

        if (($this->isAdminUserAndOrgFeatureEnabledForLogout() === true))
        {
            $user = Auth::guard('api');

            $inActivityTime = $sessionConfig['inactivity_time_admin_dashboard'] * 60;

            $isAdminUser = true;
        }

        $mobileApp = $request->header('X-Razorpay-App');

        $currentTime = time();

        $routeName = $request->route()->getName();

        if ((empty($mobileApp) === true) and (empty($user->user()) === false) and (empty($lastUsed) === false) and
            (($currentTime - $lastUsed) > $inActivityTime))
        {
            $userEmail = $user->user()->email ?? '';

            $path = '/#/access/signin';

            if ($isAdminUser === true)
            {
                $this->trace->info(TraceCode::ADMIN_LOGOUT_ON_INACTIVITY, [
                    'current_time' => $currentTime,
                    'last_used'    => $lastUsed,
                    'user_email'   => $userEmail,
                    'org_id'       => $user->user()->org_id,
                ]);

                (new Admin\Service)->logout();

                $path = '/admin';
            }
            else
            {
                $user->logout();
            }

            // if session's value is not explicitly removed
            // then it'll remain even after user is logged out
            Session::forget(UserConstants::TWO_FA_VERIFIED);

            Session::forget(UserConstants::OAUTH_LOGIN);


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

        // check if merchant user is not logged in and admin user of axis bank is logged in
        if ((empty($user->user()) === true) and
            (Auth::guard('api')->check() === true) and
            ($this->isFeatureEnabledForOrg(Auth::guard('api')->user()->org_id, self::LOGOUT_ADMIN_INACTIVITY)))
        {
           return true;
        }

        return false;
    }

    protected function isFeatureEnabledForOrg($org_id, $featureName)
    {
        $features = $this->getOrgFeatures($org_id);

        return in_array($featureName, $features);
    }

    protected function getOrgFeatures($org_id)
    {
        $features = [];

        $cacheKey = $this->getCacheKeyForOrgFeatures($org_id);

        $featuresFromCache = $this->cache->get($cacheKey);

        if (is_null($featuresFromCache) === false)
        {
            $features =  $featuresFromCache;
        }
        else
        {
            $request = new ApiRequestAny(['client_type' => 'admin']);

            list($error, $data) = $request->send("orgs/$org_id", "GET");

            $this->trace->info(TraceCode::ORG_FEATURES_CACHE_MISS, [
                'org_id' => $org_id,
            ]);

            if (empty($error))
            {
                $this->cache->put($cacheKey, $data['features'], self::CACHE_STORE_TIMEOUT_FOR_ORG_FEATURES);

                $features = $data['features'];
            }
        }

        return $features;
    }

    protected function getCacheKeyForOrgFeatures($org_id)
    {
        return 'features_' . $org_id;
    }
}

