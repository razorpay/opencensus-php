<?php

use Http\AppResponse;
use Models\User;
use Models\MerchantDetails;
use Models\Merchant;

class UserController extends BaseController
{
    public function getIndex()
    {
        return View::make('merchant.tmpgetIndex');
    }

    public function getKeepAlive()
    {
        return AppResponse::jsonResponse([]);
    }

    public function getUser()
    {
       $user = Auth::user()->user();

       $merchant = (new Merchant\Service)->fetch($user->currentMerchant->id);

       $merchantDetails = (new MerchantDetails\Service)->fetchDetails();

       $data = $merchant + $merchantDetails;

       return AppResponse::jsonResponse([], $data);
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->register($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postSignin()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->login($input);

        return AppResponse::jsonResponse($error);
    }

    public function getLogout()
    {
        Auth::user()->logout();

        return AppResponse::jsonResponse([]);
    }

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
}
