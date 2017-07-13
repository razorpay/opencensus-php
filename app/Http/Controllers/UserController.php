<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use App\Lead;
use App\User;
use App\Admin;
use App\Merchant;
use App\MerchantDetails;
use App\Http\AppResponse;
use App\User\Helper as UserHelper;

class UserController extends Controller
{
    // Users who signed up before this date
    // are not exposed to the pre signup flow

    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    protected $guard = 'users';
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
            ];
        }

        // $data is used to run diferent pieces of JS
        return view('merchant.tmpgetIndex', $data);
    }

    private function getPreSignupData($user)
    {
        $merchant = (new UserHelper)->getCurrentMerchant($user);

        if ($merchant)
        {
            return (new Merchant\Service)->getPreSignupDetails($merchant->id);
        }

        return [];
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

                (new Merchant\Service)->tagMerchant(['newui' => 'true']);
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
     * Confirms the user.
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function getConfirm($token)
    {
        list($error, $data) = (new User\Service)->confirm($token);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Log out the currently suthenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        Auth::guard('user')->logout();

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

    public function trackLead()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->createLead($input);

        return AppResponse::jsonResponse($error, $data);
    }
}
