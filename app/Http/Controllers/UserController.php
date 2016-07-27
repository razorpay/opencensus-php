<?php
namespace App\Http\Controllers;

use Auth;
use App\Http\AppResponse;
use App\User;
use App\MerchantDetails;
use App\Merchant;

use Input;

class UserController extends Controller
{

    protected $guard = 'users';
    /**
     * Returns the base template for angular.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        return view('merchant.tmpgetIndex');
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

        list($error, $data) = (new User\Service)->login($input);

        return AppResponse::jsonResponse($error);
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

    /**
     * Get the current merchant for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getOwnedMerchantForUser()
    {
        $user = Auth::user();

        list($error, $data) = (new User\Service)->getOwnedMerchantForUser($user);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * This is the one true method for all information
     * @return [type] [description]
     */
    public function getUserDetails()
    {
        $data = [
            // Current merchant
            'current'   =>  null
        ];
        $user = Auth::user();

        $merchants = $user->merchants->toArray();

        $currentMerchantId = $user->getCurrentMerchantId();

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            // Fetch merchant details for current merchant
            $data = $data + (new MerchantDetails\Service)->fetchDetails();

            foreach ($merchants as $merchant) {
                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    $data['current'] = $currentMerchantId;
                }
            }

            // And finally, for backwards compatibility
            $merchant = (new Merchant\Service)->fetchCurrentMerchantForUser($user);
            $data = $data + $merchant;
        }

        $data['user'] = $user->toArray();

        return AppResponse::jsonResponse(null, $data);
    }


    public function postUpgradeUserToMerchant()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->upgradeUserToMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }
}
