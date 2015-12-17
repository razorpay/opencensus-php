<?php

use Http\AppResponse;
use Models\User;
use Models\MerchantDetails;

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
       $user = (new User\Service)->fetch(Auth::user()->id());

       $merchantDetails = (new MerchantDetails\Service)->fetchDetails();

       $data = $user + $merchantDetails;

       return AppResponse::jsonResponse([], $data);
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
}
