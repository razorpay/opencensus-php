<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use App\Lead;
use App\User;
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
        $user = Auth::user();

        $confirmed = (bool) ($user and $user->confirmed);

        // This will be empty in case of submerchants
        $preSignup = ($user ? $this->getPreSignupData($user) : []);

        // The flow is as follows:
        //
        // If user is authenticated
        //  and verified
        //  and (has presignup complete
        //  or signed up before PRE_SIGNUP_TIMESTAMP)
        //      Then take to home page
        //  and has presignup incomplete
        //      Then show the presignup flow
        //  and not verified
        //      Then show the verification page
        $data = [
            'isAuthenticated'   => (bool) $user,
            'isConfirmed'       => $confirmed,
            'preSignupData'     => $preSignup,
        ];

        $values = array_values($data['preSignupData']);

        $data['isPreSignupComplete'] = array_reduce($values, function($carry, $item)
        {
            return $carry and !empty($item);
        }, true);

        // We don't show presignup form for user
        // created before this date
        if ($user->created_at < self::PRE_SIGNUP_TIMESTAMP)
        {
            $data['isPreSignupComplete'] = true;
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

        return false;
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
