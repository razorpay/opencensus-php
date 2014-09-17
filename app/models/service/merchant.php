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

        $this->setApiCredentials();

        try
        {
            $response = $this->api->merchant->create($merchant_api_data);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getCode());
        }

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
                $error = 'notactivated';

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
        $this->setApiCredentials(null, $mode);

        $response = $this->api->merchant
                                ->fetch($merchant_id)
                                ->keys()
                                ->all()
                                ->toArray();

        return $response;
    }

    public function createKey($merchant_id, $mode)
    {
        $errors = array();
        $data = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $data = $this->api->merchant
                                ->fetch($merchant_id)
                                ->keys()
                                ->create()
                                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getCode();
        }

        return array($errors, $data);
    }

    public function rollKeys(array $input, $mode)
    {
        list($error, $data) = Manager\Key::createValidate($input, 'create')->getData();

        if (empty($error))
        {
            $arr = Manager\Key::buildKeyUpdateData($data);

            $this->setApiCredentials(null, $mode);

            $key_data= array();
            
            try
            {
                $response = $this->api->merchant
                                    ->fetch($data['merchant_id'])
                                    ->keys()
                                    ->fetch($data['id'])
                                    ->roll($arr)
                                    ->toArray();
                
                $key_data = array(
                    'old_id' => $data['id'],
                    'merchant_id' => $input['merchant_id'],
                    'new' => $response['new']
                );
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getCode();
            }

            return array($error, $key_data);
        }
        else
            return array($error, null);
    }
}
