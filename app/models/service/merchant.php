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
            $merchant_data = DAL\Merchant::createOrFail($data)->toArray();
            
            $key_data = Manager\Merchant::generateKeyData();

            $merchant_key_data = Manager\Merchant::mergeMerchantAndKey($merchant_data, $key_data);

            Request::setCredentials($merchant_key_data['merchant_id']);
            $response = Request::POST('merchants', $merchant_key_data);

            $data = $key_data;
        }

        return [$error, $data];
    }

    public function login(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'login')->getData();

        $verify = false;

        if (empty($error))
            $verify = \Auth::attempt(array(
                'email'     => $data['email'], 
                'password'  => $input['password']
            ), $data['remember']);

        if ($verify === true)
            return [array(), $data];
        else
            return [['Email or password is invalid.'], $data];
    }
}