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
            $data['id'] = DAL\Merchant::generateId();
            
            $merchant_data = DAL\Merchant::createOrFail($data)->toArray();

            $merchant_api_data = array('id' => $merchant_data['id']);

            Request::setCredentials($merchant_api_data['id']);

            $response = Request::POST('merchants', $merchant_api_data);

            $data = array_merge($merchant_data, $response);
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
}
