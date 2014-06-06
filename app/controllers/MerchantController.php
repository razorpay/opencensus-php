<?php

use Models\Service;

class MerchantController extends BaseController {

    public function getIndex()
    {
        return View::make('merchants.getIndex');
    }

    public function getLogin()
    {
        return View::make('merchants.getLogin');
    }

    public function postLogin()
    {
        $input = Input::all();

        list($error, $data) = Service\Merchant::getInstance()->login($input);

        if (empty($error))
            return Redirect::action('MerchantController@getIndex');
        else
            return View::make('merchants.getLogin')
                ->with('data', $data)
                ->with('error', $error);
    }

    public function getRegister()
    {
        return View::make('merchants.getRegister');
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = Service\Merchant::getInstance()->register($input);

        if (empty($error))
        {
            // @todo: render this to a view; send activation mail
            die('You have registered successfully');
        }
        else
        {
            return View::make('merchants.getRegister')
                ->with('data', $data)
                ->with('error', $error);
        }
    }

    public function getTransactions()
    {
        $input = Input::all();

        $data = Service\Transaction::getInstance()->fetch($input);

        return $data;
    }

    public function getLogout()
    {
        \Auth::logout();
        return Redirect::action('MerchantController@getLogin');
    }
}
