<?php

use Http\AppResponse;
use Models\User;
use Models\MerchantDetails;
use Models\Merchant;

class UserController extends BaseController
{
    /**
     * Returns the base template for angular.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        return View::make('merchant.tmpgetIndex');
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
        Auth::user()->logout();

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

        $user = Auth::user()->user();

        $error = (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Get all the merchants for the given user.
     *
     * @param  \Models\User\Entity  $user
     * @return \Illuminate\Http\Response
     */
    public function getAllMerchantsForUser()
    {
        $user = Auth::user()->user();

        list($error, $data) = (new User\Service)->getAllMerchantsForUser($user);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Get the current merchant for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getOwnedMerchantForUser()
    {
        $user = Auth::user()->user();

        list($error, $data) = (new User\Service)->getOwnedMerchantForUser($user);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getUserDetails()
    {
        $userdata = Auth::user()->user();

        return AppResponse::jsonResponse(null, $userdata);
    }
}
