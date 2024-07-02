<?php
namespace App\Http\Controllers;

use App;
use Auth;
use View;
use Trace;
use Input;
use Cache;
use Config;
use Session;
use Request;
use Response;
use Redirect;
use App\Admin;
use OAuthFacade;
use App\Merchant;
use App\Lib\Util;
use App\Http\Headers;
use App\Admin\Entity;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\Http\SlackResponse;
use Razorpay\Api\Request as ApiRequest;
use App\Splitz\Service as SplitzService;

class AdminController extends Controller
{
    const REDIRECT_TO = '/admin';

    protected $guard = 'admin';

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;
    }

    /**
     * We always return the view, since it does not
     * contain anything sensitive
     */
    public function getIndex()
    {
        if((new Util)->debugLogsEnable()=== true)
        {
            $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                'type' => "a1",
                'session_id' => Session::getId(),
            ]);
        }

        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        $currentRouteName = \Route::currentRouteName();

        if((new Util)->debugLogsEnable()=== true)
        {
            $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                'type' => "a2",
                'Auth::guard()->check()' => Auth::guard('api')->check(),
                'currentRouteName' => $currentRouteName,
                'session_id' => Session::getId(),
            ]);
        }

        // If already logged in
        if (Auth::guard('api')->check())
        {
            $admin = $this->getAdmin()->getData(true);

            if((new Util)->debugLogsEnable() === true)
            {
                $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                    'type' => "a3",
                    'admin' => $admin,
                    'session_id' => Session::getId(),
                ]);
            }

            if (empty($admin['data']) === false)
            {
                $view = 'admin.index';
                $build_sub_path = '';
                $env = \App::environment();
                $cdn = \Config::get('app.cdn_dashboard_url');

                if ($currentRouteName === 'razorx_catchall' and (empty($org['custom_code'] === false) and ($org['custom_code'] === 'rzp'))) {
                    $view = 'admin.razorx';
                }

                if ($currentRouteName === 'capital_catchall' and (empty($org['custom_code'] === false) and ($org['custom_code'] === 'rzp'))) {
                    $view = 'admin.capital';
                    $build_sub_path = 'capital/';
                    $cdn = \Config::get('app.cdn_base_url');
                    if (($env === 'stage') or ($env === 'beta')) {
                        $branch_name = isset($_GET['branch']) ? $_GET['branch'] . '/' : 'master/';
                        $build_sub_path .= $branch_name;
                    }
                }

                return view($view, [
                    'cdn' => $cdn,
                    'build_sub_path' => $build_sub_path,
                    'org'   => $org,
                    'user'  => $admin['data'],
                ]);
            }
        }

        $code = Input::get('code');

        if (! empty($code) || config('oauth.mock'))
        {
            $oauth = $this->triggerGoogleOAuth($code);

            if (! empty($oauth))
            {
                return $oauth;
            }
        }

        // /admin/merchants → /admin, to avoid google oauth error (redirect_uri_mismatch)

        $admin_validation_route = array('admin_catchall', 'razorx_catchall', 'capital_catchall');
        if (in_array($currentRouteName , $admin_validation_route, true)) {
            $params = [
                'next' => Request::fullUrl(),
            ];
            return redirect(self::REDIRECT_TO. '?' .http_build_query($params));
        }

        switch($org['auth_type'])
        {
            case 'google_auth':
                $url = $this->getGoogleOAuthUrl();

                $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                    'google_auth_url' => $url,
                ]);

                return redirect($url);
        }

        // Password login by default
        return view('admin.login', [
            'org' => $org
        ]);
    }

    public function getMerchantStats()
    {

        $admin = $this->getAdmin()->getData(true);

        return view('admin.pokedex', [
            'cdn' => \Config::get('app.cdn_dashboard_url'),
            'user'  => $admin['data'],
        ]);
    }

    public function getCheckoutBuilder()
    {
        return view('admin.checkout-builder', [
            'cdn' => \Config::get('app.cdn_base_url')
        ]);
    }

    public function putAction($merchantId)
    {
        list($error, $data) = (new Admin\Service)->action($merchantId);

        return AppResponse::jsonResponse($error, $data);
    }

    protected function getGoogleOAuthUrl()
    {
        $googleService = OAuthFacade::consumer('Google');

        // construct state
        $state    = [];
        $nextUrl      = Request::query('next');
        if (empty($nextUrl) === false)
        {
            $state['next'] = $nextUrl;
        }
        $stateStr = base64_encode(json_encode($state));

        return (string) $googleService->getAuthorizationUri(["prompt" => "consent", 'state' => $stateStr]);
    }

    public function triggerGoogleOAuth($code)
    {
        $googleService = OAuthFacade::consumer('Google');

        // if code is provided get user data and sign in
        if ($code !== null or (config('oauth.mock') === true))
        {
            $error = (new Admin\Service)->loginWithGoogle($code, $googleService);

            if (empty($error) === true)
            {
                $state = [];
                $stateStr = Request::query('state');
                if (empty($stateStr) === false)
                {
                    $state = json_decode(base64_decode($stateStr), true);
                }

                $nextUrl = $state['next'] ?? '';
                if (empty($nextUrl) == false)
                {
                    // if next url specified is invalid, redirect to homepage
                    if (filter_var($nextUrl, FILTER_VALIDATE_URL) === false) {
                        return redirect(self::REDIRECT_TO);
                    }
                    return redirect($nextUrl);
                }

                // sort of a page reload/refresh
                return redirect(self::REDIRECT_TO);
            }
            else
            {
                return AppResponse::jsonResponse($error, []);
            }
        }
    }

    public function postSignin()
    {
        $input = Input::all();

        $domain = \Request::server('SERVER_NAME');

        // This is password based login
        list($error, $user) = (new Admin\Service)->passwordLogin($domain, $input);
        if (! empty($error))
        {
            return AppResponse::jsonResponse($error, []);
        }

        if((new Util)->debugLogsEnable()=== true)
        {
            $sessionData = Session::get(config('auth.guards.api.session_key'));
            $adminEmail = $sessionData['email'] ?? null;

            $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                'type' => "s1",
                'Auth::guard()->check()' => Auth::guard('api')->check(),
                'session_id' => Session::getId(),
                'admin_email' => $adminEmail,
            ]);
        }

        if (Auth::guard('api')->check())
        {
            return AppResponse::jsonResponse(null);
        }

        return AppResponse::jsonResponse(['Invalid Credentials'], []);
    }

    public function show2FALayout(){
        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        // If already logged in
        if (Auth::guard('api')->check())
        {
            $admin = $this->getAdmin()->getData(true);

            if (empty($admin['data']) === false)
            {
                $view = 'admin.index';

                return view($view, [
                    'cdn' => \Config::get('app.cdn_dashboard_url'),
                    'org'   => $org,
                    'user'  => $admin['data'],
                ]);
            }
        }

        return view('admin.enter-2fa', [
            'org' => $org
        ]);
    }

    public function showAccountBlocked(){
        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        // If already logged in
        if (Auth::guard('api')->check())
        {
            $admin = $this->getAdmin()->getData(true);

            if (empty($admin['data']) === false)
            {
                $view = 'admin.index';

                return view($view, [
                    'cdn' => \Config::get('app.cdn_dashboard_url'),
                    'org'   => $org,
                    'user'  => $admin['data'],
                ]);
            }
        }

        return view('admin.account_block', [
            'org' => $org
        ]);
    }

    public function forgotPassword(){
        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        // If already logged in
        if (Auth::guard('api')->check())
        {
            $admin = $this->getAdmin()->getData(true);

            if (empty($admin['data']) === false)
            {
                $view = 'admin.index';

                return view($view, [
                    'cdn' => \Config::get('app.cdn_dashboard_url'),
                    'org'   => $org,
                    'user'  => $admin['data'],
                ]);
            }
        }

        return view('admin.forgot-password', [
            'org' => $org
        ]);
    }

    public function postForgotPassword() {

        $input = Input::all();
        $domain = \Request::server('SERVER_NAME');
        list($error, $response) = (new Admin\Service)->triggerPassResetEmail($domain, $input);
        return AppResponse::jsonResponse($error, $response);
    }

    public function postResetPassword() {

        $input = Input::all();
        $domain = \Request::server('SERVER_NAME');
        list($error, $response) = (new Admin\Service)->changePassword($domain, $input);
        return AppResponse::jsonResponse($error, $response);
    }


    public function resetPassword(){
        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        // If already logged in
        if (Auth::guard('api')->check())
        {
            $admin = $this->getAdmin()->getData(true);

            if (empty($admin['data']) === false)
            {
                $view = 'admin.index';

                return view($view, [
                    'cdn' => \Config::get('app.cdn_dashboard_url'),
                    'org'   => $org,
                    'user'  => $admin['data'],
                ]);
            }
        }

        return view('admin.reset-password', [
            'org' => $org
        ]);
    }

    public function postResendOtp()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postResendOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }


    public function postVerify2faAuthOtp()
    {

        $input = Input::all();

        $input = $this->putSessionValue($input);

        list($error, $data) = (new Admin\Service)->twoFactorAuthVerifyOtp($input);

        if (! empty($error))
        {
            return AppResponse::jsonResponse($error, []);
        }

        if (Auth::guard('api')->check())
        {
            return AppResponse::jsonResponse(null);
        }

        return AppResponse::jsonResponse(['Incorrect OTP'], []);
    }

    public function getOrg()
    {
        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = (new Admin\Service)->getOrg($domain);

        $this->changeAuthTypeToPasswordForItfTest($error, $org);

        return AppResponse::jsonResponse($error, $org);
    }

    public function getOrgByDomainName()
    {
        $input = Input::all();

        $domain = $input['domainName'];

        list($error, $org) = (new Admin\Service)->getOrg($domain);

        $this->changeAuthTypeToPasswordForItfTest($error, $org);

        return AppResponse::jsonResponse($error, $org);
    }

    public function getAdmin()
    {
        // We are not caching admin data in session because permissions,
        // roles, etc. might change
        //
        // So for now every time the user refreshes the page
        // we will load entire payload and give it to the frontend
        // to work with.
        //
        // Later at some point we'll have to shove all these data in
        // redis and that'll work well.

        $admin = Auth::guard('api')->user();

        list($error, $data) = (new Admin\Service)->getAdminData($admin);

        if (! empty($error))
        {
            Auth::guard('api')->logout();
        }

        return AppResponse::jsonResponse($error, $data);
    }

    public function getAdminActivity()
    {
        $id = Auth::guard('api')->user()->id;

        $activity = (new Admin\Service)->getAdminActivity($id);

        return AppResponse::jsonResponse([], $activity);
    }

    public function deleteOtherAdminActivity()
    {
        $id = Auth::guard('api')->user()->id;

        (new Admin\Service)->deleteAllOtherAdminSessions($id);

        return AppResponse::jsonResponse([]);
    }

    public function deleteAdminActivity($sessionId)
    {
        (new Admin\Service)->deleteOneAdminSessions($sessionId);

        return AppResponse::jsonResponse([]);
    }

    public function getLogout()
    {
        list($error, $data) = (new Admin\Service)->logout();

        return AppResponse::jsonResponse($error, $data);
    }

    public function getKeepAlive()
    {
        $error = [];
        $response = (new Admin\Service)->updateKeepAlive();

        if ($response === false)
        {
            // Auth::admin()->logout();
            // $error = ['You have been logged out'];
        }

        return AppResponse::jsonResponse($error, $response);
    }

    public function getMerchantLogin($id)
    {
        $error = (new Admin\Service)->loginUsingPrimaryOwner($id);

        if (empty($error) === false)
        {
            return AppResponse::jsonResponse($error);
        }

        $originDomain = \Request::server('HTTP_X_ORIGIN_PRODUCT');

        if ($originDomain === config('app.banking_service_url'))
        {
            return AppResponse::jsonResponse(null);
        }

        return redirect('/');
    }

    public function postMerchantTerminal($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantTerminal($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantActivation($id)
    {
        $dashboardOnly = Input::get('dashboard', false);

        list($error, $data) = (new Admin\Service)->activateMerchant($id, $dashboardOnly);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Calls the Creevey service over a queue to capture screenshots
     * @param  string $id Merchant Id
     */
    public function captureMerchantScreenshot($id)
    {
        $error = (new Admin\Service)->captureScreenshot($id);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Returns an HTML View for now
     * @param  string $id merchant id
     */
    public function getMerchantScreenshot($id)
    {
        $links = (new Admin\Service)->getScreenshot($id);

        return View::make('admin.screenshots', ['links' => $links]);
    }

    public function saveMerchantScreenshot($id)
    {
        $input = \Input::all();

        $error = (new Admin\Service)->saveScreenshot($id, $input);

        return AppResponse::jsonResponse($error);
    }

    public function postEditMerchant($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchant($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putEditMerchantEmail($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchantEmail($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMultipleEntities($mode, $entity, $format = 'json')
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->fetchMultipleEntities($mode, $entity, $input, true);

        if ($format === 'csv' and empty($error) === true)
        {
            //(new Admin\Service)->logDataExport($entity, $input);
            $items = $data['items'] ?? $data;

            return AppResponse::csvResponse($items);
        }
        else
        {
            return AppResponse::jsonResponse($error, $data);
        }
    }

    public function getMerchantHdfcExcel($id)
    {
        list($error, $file) = (new Admin\Service)->generateMerchantHdfcExcel($id);

        if (empty($error) === false)
            return AppResponse::jsonResponse($error);

        $file->download('xlsx');
    }

    public function passThrough($path = '')
    {
        list($error, $response) = (new Admin\Service)->makeRawApiCall($path);

        return AppResponse::jsonResponse($error, $response);
    }

    public function addEntityFeatures($entityType, $entityId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->addEntityFeatures($entityType, $entityId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postSlackQuery()
    {
        $input = Input::all();

        list($message, $data) = (new Admin\Service)
            ->querySlack($input);

        return SlackResponse::jsonResponse($message, $data);
    }

    public function getMerchantAggregations($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->getMerchantAggregations($mode, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSingleMerchantAggregations($mode, $merchant)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->getSingleMerchantAggregations($mode, $input, $merchant);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getCompanyInfo($cin)
    {
        $company = new Admin\Company($cin);
        return AppResponse::jsonResponse([], $company->fetch());
    }

    public function postReconciliate($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Admin\Service)->makeReconciliateRequest($input, $mode);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
    * Expects date input in format "3 august 2016"
    */
    public function updateDayAggregations($mode)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->updateMerchantDayAggregations($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    // ----- Heimdall (Whitelabel) -----

    public function postUploadOrgLogo($orgId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->uploadOrgLogo($orgId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postUploadOrgBackgroundImage($orgId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->uploadOrgBackgroundImage($orgId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function getStatus()
    {
        list($response, $statusCode) = (new Admin\Service)->getStatus();

        return Response::json($response, $statusCode);
    }

    public function getEmailLogs()
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->getEmailLogs($input);

        return AppResponse::jsonResponse($error, $response);
    }


    private function putSessionValue($input)
    {
        return (new Admin\Service)->putSessionValues($input);
    }

    private function changeAuthTypeToPasswordForItfTest(&$error, &$org)
    {
        $env = \App::environment();

        $domain = \Request::server('SERVER_NAME');

        $devServe = Request::header(Headers::DEV_SERVE_USER);

        if ((empty($error) === true) and
            (empty(Request::header(Headers::DEV_SERVE_USER)) === false) and
            (str_starts_with($devServe, 'itf') || str_starts_with($devServe, 'pr-')) and
            ($env === 'stage') and
            (($domain === 'dashboard-' . Request::header(Headers::DEV_SERVE_USER) . '.dev.razorpay.in') or
                ($domain === 'dashboard-' . Request::header(Headers::DEV_SERVE_USER) . '.int.dev.razorpay.in') or
                ($domain === 'dashboard.dev.razorpay.in') or ($domain === 'dashboard.int.dev.razorpay.in')
            ))
        {
            $org['auth_type'] = 'password';
        }
    }

    // invalidate the complete splitz cache
    public function clearSplitzCache() {

        $input = Input::all();

        $error = (new App\Splitz\Validator)->validateInput('splitz_invalidation', $input)->messages();

        if(empty($error) === false)
        {
            return [$error, []];
        }

        $data = (new SplitzService())->clearSplitzCache($input);

        return AppResponse::jsonResponse([], $data);
    }

    // Invalidate the /org cache for all domains
    public function clearOrgCache() {

        $data = (new Admin\Service)->clearOrgCacheForAllDomains();

        return AppResponse::jsonResponse([], $data);
    }

    public function getSso()
    {
        $url = (new Admin\Service())->getSso();

        return redirect($url);
    }

    public function postCallback()
    {
        $data = Input::all();

        return (new Admin\Service())->postCallback($data);
    }

    // invalidate the complete razorX cache
    public function clearRazorXCache() {

        $input = Input::all();

        $error = (new App\Razorx\Validator)->validateInput('razorx_invalidation', $input)->messages();

        if(empty($error) === false)
        {
            return [$error, []];
        }

        $data = (new App\Razorx\Service())->clearRazorxCache($input);

        return AppResponse::jsonResponse([], $data);
    }
}
