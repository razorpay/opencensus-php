<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Merchant extends Service
{
    public function register(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'register')->getData();

        if (empty($error))
        {   
            $data = DAL\Merchant::createOrFail($data)->toArray();

            $this->sendConfirmationMail($data);

        }

        return [$error, $data];
    }

    public function confirm($token)
    {
        $merchant = new DAL\Merchant;

        try
        {
            $merchant = $merchant->getMerchantForConfirmation($token);
        }
        catch(\Exception $e)
        {
            return false;
        }

        $merchant->confirm();

        $merchant_api_data = $merchant->generateApiData($merchant);

        Request::setCredentials($merchant_api_data['id']);

        $response = Request::POST('merchants', $merchant_api_data);

        return array_merge($response, $merchant->toArray());
    }

    public function login(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'login')->getData();

        $verify = false;

        if (empty($error))
            $verify = \Auth::attempt(array(
                'email'     => $data['email'],
                'password'  => $input['password'],
                'confirm_token' => Null
            ), $data['remember']);

        if ($verify === true)
            return [array(), $data];
        else
            return [['Email or password is invalid.'], $data];
    }

    public function fetch($merchant_id)
    {
        $merchant = DAL\Merchant::findOrFail($merchant_id)->toArray();

        return $merchant;
    }

    public function fetchKeysFromApi($merchant_id)
    {
        Request::setCredentials($merchant_id);
        $response = Request::GET('keys');

        return $response;
    }

    public function rollKeys(array $input)
    {
        list($error, $data) = Manager\Key::createValidate($input, 'create')->getData();

        if (empty($error))
        {
            $arr = Manager\Key::buildKeyUpdateData($data);

            Request::setCredentials($data['merchant_id']);

            $url = 'keys/'.$data['id'];

            $response = Request::PUT($url, $arr);

            if ((isset($response['old']) === false) or
                (isset($response['new']) === false))
            {
                return ['status' => false];
            }

            $key_data = array(
                'old_id' => $data['id'],
                'merchant_id' => $input['merchant_id'],
                'key_id' => $response['new']['id'],
                'secret' => $response['new']['secret'],
                'status' => true);

            return $key_data;
        }
        else
            return ['status' => false];
    }

    private function sendConfirmationMail($merchant)
    {
        return \Mail::queue('emails.confirmation', compact('merchant'), function($m) use ($merchant)
        {
            $m->to($merchant['email'], $merchant['name'])->subject('Welcome to Razorpay!');
        });
    }
}
