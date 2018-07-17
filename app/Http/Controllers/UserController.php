<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use App\User;
use App\Admin;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Http\AppResponse;

class UserController extends Controller
{
    // Users who signed up before this date
    // are not exposed to the pre signup flow

    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    protected $guard = 'users';

    protected $app;

    protected $trace;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    /**
     * Returns the base template for angular.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $domain = \Request::server('SERVER_NAME');
        list($orgError, $org) = (new Admin\Service)->getOrg($domain);
        list($userError, $details) = (new User\Service)->getUserDetails();

        $data = [
            'isAuthenticated'       => false,
            'isConfirmed'           => false,
            'preSignupData'         => [],
            'isPreSignupComplete'   => false,
        ];

        if (empty($userError) and empty($orgError))
        {
            $data = [
                'isAuthenticated'       => (bool) $details['user'],
                'isConfirmed'           => $details['user']['confirmed'],
                'preSignupData'         => $details['pre_signup'],
                'isPreSignupComplete'   => $details['pre_signup_complete'],
                'user'                  => json_encode($details),
                'org'                   => json_encode($org),
                'api_host'              => ApiUrl::getApiBaseUrl(),
            ];
        }

        $data['cdnDashboardUrl'] = \Config::get('app.cdn_dashboard_url');

        // $data is used to run diferent pieces of JS
        if (isset($data['user']) === true and isset($data['linked_account']) === true and $data['linked_account'] === true) {
            return view('merchant.la', $data);
        } else {
            return view('merchant.index', $data);
        }
    }

    /**
     * Returns an empty success to keep the user session active..
     *
     * @return \Illuminate\Http\Response
     */
    public function getKeepAlive()
    {
        return AppResponse::jsonResponse([]);
    }

    public function postRegister()
    {
        $input = Input::all();

        $data = null;

        $error = [];

        try
        {
            list($error, $data) = (new User\Service)->register($input);

            if (empty($error))
            {
                $credentials = [
                    'email'     => $input['email'],
                    'password'  => $input['password']
                ];

                Auth::attempt($credentials, false, true);
            }
        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
        }

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSignin()
    {
        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->login($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Log out the currently authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        $user = Auth::guard('user');

        $userDetails = $user->user();

        $traceData = [
            'id'          => $userDetails->id,
            'email'       => $userDetails->email,
            'merchant_id' => $userDetails->currentMerchant()->id,
        ];

        $this->trace->info(TraceCode::USER_LOGOUT, $traceData);

        $user->logout();

        return AppResponse::jsonResponse([]);
    }

    /**
     * Handle the request from user to change his current password.
     *
     * @return \Illuminate\Http\Response
     */
    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->changePassword($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return \Illuminate\Http\Response
     */
    public function switchCurrentMerchant($merchantId)
    {
        $input = Input::all();

        $user = Auth::user();

        $error = (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);

        return AppResponse::jsonResponse($error);
    }

    public function getUserDetailsV2()
    {
        list($error, $data) = (new User\Service)->getUserDetails();

        return AppResponse::jsonResponse($error, $data);
    }

    public function postUpgradeUserToMerchant()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->upgradeUserToMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Fetch data for the currently active user session
     *
     * @return mixed
     */
    public function getSessionData()
    {
        $queryParams = Input::all();

        list($error, $data) = (new User\Service)->getSessionData($queryParams);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * The auth-service gets details of the currently logged in user
     * using this route (once it has the token)
     *
     * @param string $token
     *
     * @return \Illuminate\Http\Response
     */
    public function getDetailsFromToken(string $token)
    {
        list($error, $data) = (new User\Service)->getDetailsFromSessionToken($token);

        $response = AppResponse::jsonResponse($error, $data);

        return $response;
    }
}
