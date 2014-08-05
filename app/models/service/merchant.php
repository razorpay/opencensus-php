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
            $merchant = DAL\Merchant::createOrFail($data);

            $merchant_details = DAL\MerchantDetails::createOrFail(array('merchant_id'=>$merchant->id));

            \Queue::push('MerchantController@sendConfirmationMail',array('merchant' => $merchant->generateEmailData()));

            $data = $merchant->toArray();
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

        $merchant_api_data = $merchant->generateApiData($merchant);

        $request = (new Request)->setCredentials();

        $response = $request->POST('merchants', $merchant_api_data);

        if(isset($response['error'])) return false;

        $merchant->confirm();

        return array_merge($response, $merchant->toArray());
    }

    public function login(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'login')->getData();

        $verify = false;

        if (empty($error))
            $verify = \Auth::merchant()->attempt(array(
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
        $request = (new Request)->setCredentials();
        $response = $request->GET('merchants/'.$merchant_id.'/keys');

        return $response;
    }

    public function rollKeys(array $input)
    {
        list($error, $data) = Manager\Key::createValidate($input, 'create')->getData();

        if (empty($error))
        {
            $arr = Manager\Key::buildKeyUpdateData($data);

            $request = (new Request)->setCredentials();

            $url = 'merchants/'.$data['merchant_id'].'/keys/'.$data['id'];

            $response = $request->PUT($url, $arr);

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
