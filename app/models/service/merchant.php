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
            
            $key_data = Manager\Key::generateKeyData();

            $merchant_key_data = Manager\Merchant::mergeMerchantAndKey($merchant_data, $key_data);

            Request::setCredentials($merchant_key_data['merchant_id']);
            $response = Request::POST('merchants', $merchant_key_data);

            $data = array_merge($merchant_data, $key_data);
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

    public function fetch($merchant_id)
    {
        $merchant = DAL\Merchant::findOrFail($merchant_id)->toArray();

        return $merchant;
    }

    public function fetchKeysFromApi($merchant_id)
    {
        Request::setCredentials($merchant_id);
        $response = Request::GET('merchants/keys');

        return $response;
    }

    public function rollKeys(array $input)
    {
        list($error, $data) = Manager\Key::createValidate($input, 'create')->getData();

        if (empty($error))
        {
            $key_data = Manager\Key::generateKeyData();
            $key_data['status'] = true;

            $arr = Manager\Key::buildKeyUpdateData($data, $key_data);

            Request::setCredentials($data['merchant_id']);
            $response = Request::PUT('merchants/keys', $arr);

            if ($response->status === false)
                return ['status' => false];
            else
                return $key_data;
        }
        else
            return ['status' => false];
    }
}
