<?php
namespace App\Http\Controllers;

use App;
use App\Trace\TraceCode;
use Auth;
use Input;
use Config;
use Request;
use App\Admin;

use App\Generic;
use App\Http\AppResponse;
use App\Session\Entity as AppSession;

class GenericController extends Controller
{
    const WHITELISTED_HEADERS = [
        'x-consumer',
        'x-report-type',
        'x-cross-org-id',
        'x-org-id',
    ];

    const WHITELISTED_ROUTES_REGEX = [
        '^invoices$',
        '^currency\/all\/proxy$',
        'invoices\/inv_[[:alnum:]]{14}\/notify_by\/(?:email|sms)$',
        '^invoices\/inv_[[:alnum:]]{14}\/cancel$'
    ];

    const USERS_LOGIN_SIGNUP_ROUTES = [
        'users/login',
        'users/register',
        'users/login/no2fa',
        'users/oauth-login/no2fa',
        'users/oauth-login',
        'users/oauth-register',
    ];

    const PATH_VS_COOKIE = [
        'merchant/activation' => ['client_id']
    ];

    const USERS_RESET_PASSWORD_PATH  = 'users/reset-password-token';

    const MERCHANT_BULK_ACTION_ROUTE = 'merchants/bulk';

    const SUSPEND                    = 'suspend';

    const UNSUSPEND                  = 'unsuspend';

    public function handleAny($mode, $path)
    {
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

        if (($path === self::USERS_RESET_PASSWORD_PATH) &&
            ($httpCode === 200) &&
            (isset($data['user_id']) === true))
        {
            (new AppSession)->deleteSessionsForUser($data['user_id']);
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
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
}
