<?php
namespace App\Http\Controllers;

use App;
use App\Trace\TraceCode;
use App\User\Constants;
use Auth;
use Route;
use Input;
use Config;
use App\Admin;
use Request;

use App\User;
use App\Generic;
use App\Http\AppResponse;
use App\Session\Entity as AppSession;

use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\BadRequestError;

class GenericController extends Controller
{
    const WHITELISTED_HEADERS = [
        'x-consumer',
        'x-report-type',
        'x-cross-org-id',
        'x-org-id',
        'accept-version',
        'x-razorpay-nca-transform'
    ];

    const WHITELISTED_ROUTES_REGEX = [
        '^invoices$',
        '^currency\/all\/proxy$',
        'invoices\/inv_[[:alnum:]]{14}\/notify_by\/(?:email|sms)$',
        '^invoices\/inv_[[:alnum:]]{14}\/cancel$'
    ];

    const EPOS_BLOCKED_ROUTES_REGEX = [
        '^invoices$',
        '^invoices\/inv_[[:alnum:]]{14}\/notify_by\/sms$',
    ];

    const USERS_LOGIN_SIGNUP_ROUTES = [
        'users/login',
        'users/register',
        'users/login/no2fa',
        'users/oauth-login/no2fa',
        'users/oauth-login',
        'users/oauth-register',
        'users/login/otp',
        'users/login/otp/verify',
        'users/login/otp/2fa',
        'users/login/verification-otp',
        'users/login/verification-otp/verify',
        'users/register/otp',
        'users/register/otp/verify',
    ];

    const PATH_VS_COOKIE = [
        'merchant/activation' => ['clientId']
    ];

    const ACCESS_TOKENS = [
        'x-mobile-client-id',
        'x-mobile-access-token',
        'x-mobile-refresh-token',
    ];

    const USERS_RESET_PASSWORD_PATH           = '^users\/reset-password-token$';
    const MERCHANT_EMAIL_UPDATE               = '^merchants\/email\/update$';
    const MERCHANT_EMAIL_UPDATE_NEW_USER      = '^merchants\/email\/update\/create_user$';
    const USER_DETACH_FROM_MERCHANT_TEAM      = '^users\/[[:alnum:]]{14}\/detach$';

    const ROUTES_FOR_SESSION_DELETE = [
        self::USERS_RESET_PASSWORD_PATH,
        self::MERCHANT_EMAIL_UPDATE,
        self::MERCHANT_EMAIL_UPDATE_NEW_USER,
        self::USER_DETACH_FROM_MERCHANT_TEAM,
    ];

    const ROUTES_FOR_ID_IN_DATA = [
        self::USER_DETACH_FROM_MERCHANT_TEAM,
    ];

    const LOGOUT_SESSIONS_FOR_USERS = 'logout_sessions_for_users';

    const MERCHANT_BULK_ACTION_ROUTE = 'merchants/bulk';

    const SUSPEND                    = 'suspend';

    const UNSUSPEND                  = 'unsuspend';

    const EMANDATE_SERVICE = 'emandate/report/bounce-memo';

    public function handleAny($mode, $path = null)
    {
        $app = App::getFacadeRoot();

        if ($mode === null)
        {
            $app['trace']->info(TraceCode::OPTIONS_ROUTE_ERROR_MODE_NULL, [
                'mode' => $mode,
                'path' => $path,
            ]);
        }

        else if ($mode === "null")
        {
            $app['trace']->info(TraceCode::OPTIONS_ROUTE_MODE_AS_NULL_ERROR, [
                'mode' => $mode,
                'path' => $path,
            ]);
        }

        else if (((str_starts_with($mode, 'live') === false) and
                (str_starts_with($mode, 'test') === false)) or
                ($path === null))
        {
            $app['trace']->info(TraceCode::OPTIONS_ROUTE_ERROR_MODE_ERROR, [
                'mode' => $mode,
                'path' => $path,
            ]);
        }

        // Epos App Deprecated. Blocking Signin for Epos App Users
        $mobileApp = \Request::header('X-Razorpay-App');

        $eposBlockedRoutesRegex = implode('|', self::EPOS_BLOCKED_ROUTES_REGEX);

        if ((empty($mobileApp) === false) and
            ($mobileApp === 'Epos') and
            (preg_match('/' . $eposBlockedRoutesRegex . '/', $path, $pathMatches) == true))
        {
            $app = App::getFacadeRoot();

            $app['trace']->info(TraceCode::ROUTE_BLOCKED_EPOS_APP, [
                'path' => $path,
            ]);

            $response =  User\Constants::EPOS_APP_DEPRECATED_MESSAGE;

            return AppResponse::jsonResponse($response);
        }

        $allRequestHeaders = Request::header();
        $input = Input::all();

        $headers = [];

        foreach($allRequestHeaders as $key => $value) {
            $key = strtolower($key);

            if (in_array($key, self::WHITELISTED_HEADERS, true) === true) {
                $key = title_case($key);

                $headers[$key] = $value[0];
            }
        }

        $request = new App\Admin\ApiRequestAny([
            'mode'      => $mode,
            'headers'   => $headers,
        ]);

        $method = Request::method();

        $app = App::getFacadeRoot();

        $app['trace']->info(TraceCode::GENERIC_ROUTE_PATH, ['path' => $path, 'method' => $method]);

        $checkUsersRoute = $this->checkAndBlockUserRoutes($path, $method);

        if ($checkUsersRoute === true)
        {
            return AppResponse::unauthorizedResponse('Unauthorized user', Request::route()->getName(), $path);
        }

        /**
         * This is a temporary fix
         * for more info look 👉🏻 https://razorpay.atlassian.net/browse/MCOB-3309
         *  */
        if (str_contains($path, 'users/reset-password') and $method === 'POST')
        {
            if ((isset($input['email']) === true and
                (in_array($input['email'], Constants::BLOCKED_EMAILS_FOR_LOGIN, true) === true)))
            {
                return AppResponse::jsonResponse(null, [
                    "success" => true
                ], 200);
            }
        }

        /**
         * This is a temporary check to prevent a security vulnerability
         * for more info look 👉🏻 https://jira.corp.razorpay.com/browse/SBB-622
         *  */
        if (str_contains($path, 'admin/payout') and $method === 'GET')
        {
            if ((isset($input['merchant_id']) === true and $input['merchant_id'] === 'G7MHzUX7Vbzbz7'))
            {
                $app['trace']->info(TraceCode::BLOCKED_DUE_TO_SBB_622, []);

                $error = [
                    'Access Blocked for the this merchant\'s payout entity from dashbaord BE',
                    '400',
                ];

                return AppResponse::jsonResponse($error, null, 403);
            }
        }

        if(array_key_exists($path,self::PATH_VS_COOKIE) === true)
        {
            $request->addCookiesForPath(self::PATH_VS_COOKIE[$path]);
        }

        list($error, $data, $httpCode) = $request->send($path, $method);

        if (($path === self::MERCHANT_BULK_ACTION_ROUTE) and
            (isset($input['action']) === true) and
            (($input['action'] === self::SUSPEND) or ($input['action'] === self::UNSUSPEND)))
        {
            (new Admin\Service())->clearMerchantsUserSessions($input['merchant_ids']);
        }

        $this->checkAndDeleteUserSessions($path, $data, $httpCode);

        $headers = $this->getMobileOauthHeaders($data);

        return AppResponse::jsonResponse($error, $data, $httpCode, $headers);
    }


    public function bounceMemo()
    {
        $allRequestHeaders = Request::header();

        $headers = [];

        foreach($allRequestHeaders as $key => $value) {
            $key = strtolower($key);

            if (in_array($key, self::WHITELISTED_HEADERS, true) === true) {
                $key = title_case($key);

                $headers[$key] = $value[0];
            }
        }

        $request = new App\Admin\ApiRequestAny([]);

        $method = 'post';

        $app = App::getFacadeRoot();

        try{
            list($error, $data, $httpCode) = $request->send(self::EMANDATE_SERVICE, $method);
        }
        catch (\Throwable $e){
            $app['trace']->info(TraceCode::BOUNCE_MEMO_REQUEST_FAILED, ['path' => self::EMANDATE_SERVICE, 'method' => $method]);
        }
        return AppResponse::jsonResponse($error, $data, $httpCode, $headers);

    }

    protected function checkAndDeleteUserSessions($path, $data, $httpCode)
    {
        $userIDs = $this->getUserIDsForLogout($data, $path);

        $routesForSessionDeleteRegex = implode('|', self::ROUTES_FOR_SESSION_DELETE);

        if ((preg_match('/' . $routesForSessionDeleteRegex . '/', $path, $pathMatches) == true) and
            ($httpCode === 200) and
            (is_null($userIDs) === false))
        {
            $this->traceLogOutUserIDs($userIDs, $path);

            $appSession = (new AppSession);

            foreach ($userIDs as $userID)
            {
                $appSession->deleteSessionsForUser($userID);
            }
        }
    }

    protected function getUserIDsForLogout($data, $path)
    {
        $routesForIdInDataRegex = implode('|', self::ROUTES_FOR_ID_IN_DATA);

        if (preg_match('/' . $routesForIdInDataRegex . '/', $path, $pathMatches) == true)
        {
            if (isset($data['id']) === true)
            {
                return [
                    $data['id']
                ];
            }

            $app = App::getFacadeRoot();

            $app['trace']->info(TraceCode::USER_SESSION_LOGOUT_FAIL, [
                'route' => $path,
            ]);

            return null;
        }

        if (isset($data[self::LOGOUT_SESSIONS_FOR_USERS]) === true)
        {
            return $data[self::LOGOUT_SESSIONS_FOR_USERS];
        }

        elseif (isset($data['user_id']) === true)
        {
            return [
                $data['user_id']
            ];
        }

        return null;
    }

    protected function traceLogOutUserIDs($userIDs, $path)
    {
        $app = App::getFacadeRoot();

        $app['trace']->info(TraceCode::USERS_LOGOUT_BY_ROUTE, [
            'route' => $path,
            'users' => $userIDs
        ]);
    }

    /**
     * Disable User login and User register routes from generic controller for security reasons.
     *
     * @param $path
     * @param $method
     *
     * @return bool
     */
    protected function checkAndBlockUserRoutes($path, $method)
    {
        if (in_array($path, self::USERS_LOGIN_SIGNUP_ROUTES, true) === true)
        {
            $app = App::getFacadeRoot();

            $app['trace']->info(TraceCode::USER_UNAUTHORIZED_GENERIC_EXCEPTION, ['path' => $path, 'method' => $method]);

            return true;
        }

        return false;
    }

    public function handleAnyExtension($mode, $path)
    {
        $whiteListedRoutesRegex = implode('|', self::WHITELISTED_ROUTES_REGEX);

        if (preg_match('/' . $whiteListedRoutesRegex . '/', $path, $pathMatches) == true)
        {
            $request = new App\Admin\ApiRequestAny([
                'mode'      => $mode,
                'headers'   => [
                    'X-Chrome-Extension'=> true,
                ],
            ]);

            $method = Request::method();

            list($error, $data) = $request->send($path, $method);

            return AppResponse::jsonResponse($error, $data);
        }

        return AppResponse::unauthorizedResponse('Unauthorized.', Request::route()->getName(), $path);
    }

    /**
     * This request is for handling public growth assets (from growthpod)
     * This is served directly from edge <> growth-service in prod
     * This implementation is only to support devstack env
     * @return mixed
     */
    public function getPublicGrowthAssets()
    {
        $request = new App\Admin\ApiRequestAny();
        $method = Request::method();
        list($error, $data, $httpCode) = $request->send('growth/public/assets', $method);
        return AppResponse::jsonResponse($error, $data, $httpCode);
    }
}
