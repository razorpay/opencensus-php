<?php

use Models\Service;

class MerchantController extends BaseController
{
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
            return Redirect::action('MerchantController@getLogin')
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
            return View::make('merchants.postRegister')
                        ->with('data', $data);
        }
        else
        {
            return Redirect::action('MerchantController@getRegister')
                ->with('data', $data)
                ->with('error', $error);
        }
    }

    public function getLogout()
    {
        \Auth::logout();
        return Redirect::action('MerchantController@getLogin');
    }

    public function getCsv()
    {
        $input = Input::only(
            'id', 'secret'
        );

        if (!isset($input['id']) || !isset($input['secret']))
            return;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rzp.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('rzp_id', 'rzp_secret'));
        fputcsv($output, array($input['id'], $input['secret']));
    }

    public function getAccount()
    {
        $merchant = Service\Merchant::getInstance()->fetch(\Auth::id());

        return $merchant;
    }

    public function getKeys()
    {
        $keys = Service\Merchant::getInstance()->fetchKeysFromApi(\Auth::id());

        return $keys;
    }

    public function postKeys()
    {
        $input = Input::all();

        $input['merchant_id'] = \Auth::id();

        $data = Service\Merchant::getInstance()->rollKeys($input);

        return $data;
    }

    public function getConfirm($token)
    {
        $response = Service\Merchant::getInstance()->confirm($token);

        if($response)
        {   
            \Auth::loginUsingId($response['id']);

            return View::make('merchants.getKeys')
                        ->with('data', $response);
        }
        else
        {
            return Redirect::action('MerchantController@getRegister')
                ->with('error', array('An error occured in email verification. Please check the link and try again'));
        }
    }

    public function sendConfirmationMail($job, $data)
    {
        $merchant = $data['merchant'];

        \Mail::send('emails.confirmation', compact('merchant'), function($m) use ($merchant)
        {
            $m->to($merchant['email'], $merchant['name'])->subject('Welcome to Razorpay!');
        });

        $job->delete();
    }
}
