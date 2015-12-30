<?php

use Http\AppResponse;
use Models\User;
use Models\MerchantDetails;

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
}
