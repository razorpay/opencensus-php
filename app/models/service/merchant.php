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

    public function changePassword(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'password')->getData();

        if (empty($error))
        {   
            $merchant = \Auth::merchant()->user();

            $old_password = $data['old_password'];

            unset($data['old_password']);

            if (\Hash::check($old_password, $merchant->password) == false)
            {
                $error[] = 'Incorrect password';

                return [$error, $data];
            }

            $merchant->update($data);
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
            return array('Invalid Confirmation Token');
        }

        $merchant_api_data = $merchant->generateApiData();

        $request = (new Request)->setCredentials();

        $response = $request->process('POST', 'merchants', $merchant_api_data);

        if(isset($response['error'])) return array($response['error']['description']);

        $merchant->confirm();

        return array();
    }

    public function login(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'login')->getData();

        if (empty($error)) 
        {      
            $credentials = array(
                'email'     => $data['email'],
                'password'  => $input['password']
            );

            if(\Auth::merchant()
                        ->attempt($credentials + array('confirm_token' => Null))) 
            {
                return [array(), $data];
            }
            elseif(\Auth::merchant()->validate($credentials))
            {
                $error = 'Email Id not confirmed. Please check your inbox for confirmation mail or <a href="'.\URL::to('#/access/resend').'">click here to resend</a> it.';

                return [[$error], $data];
            }
        }
        
        return [['Email or password is invalid.'], $data];
    }

    public function resendConfirmation(array $input)
    {
        list($error, $data) = Manager\Merchant::createValidate($input, 'login')->getData();

        if (empty($error)) 
        {      
            $credentials = array(
                'email'     => $data['email'],
                'password'  => $input['password']
            );

            if(\Auth::merchant()
                        ->once($credentials)) 
            {
                $merchant = \Auth::merchant()->get();

                if($merchant->confirm_token === NULL)
                {
                    return [['Merchant already confirmed. You can login <a href="'.\URL::to('#/access/signin').'">here</a>'], $data];
                }

                \Queue::push('MerchantController@sendConfirmationMail',array('merchant' => $merchant->generateEmailData()));
                
                return [[], $data];
            }
        }
        
        return [['Email or password is invalid.'], $data];
    }

    public function fetch($merchant_id)
    {
        $merchant = DAL\Merchant::findOrFail($merchant_id)->toArray();

        return $merchant;
    }

    public function fetchKeysFromApi($merchant_id, $mode)
    {
        $request = (new Request)->setCredentials($mode);
        $response = $request->process('GET', 'merchants/'.$merchant_id.'/keys');

        return $response;
    }

    public function createKey($merchant_id, $mode)
    {
        $request = (new Request)->setCredentials($mode);

        $response = $request->process('POST', 'merchants/'.$merchant_id.'/keys');
        
        if(isset($response['error']))
        {
            throw new \Exception('API responded with error');
        }

        return $response;
    }

    public function rollKeys(array $input, $mode)
    {
        list($error, $data) = Manager\Key::createValidate($input, 'create')->getData();

        if (empty($error))
        {
            $arr = Manager\Key::buildKeyUpdateData($data);

            $request = (new Request)->setCredentials($mode);

            $url = 'merchants/'.$data['merchant_id'].'/keys/'.$data['id'];

            $response = $request->process('PUT', $url, $arr);

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
            );

            return array($error, $key_data);
        }
        else
            return array($error, null);
    }
}
