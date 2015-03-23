<?php

namespace Models\Merchant;

use Models\Base;
use Models\Merchant;
use Models\MerchantDetails;
use Mail;

class Service extends Base\Service
{
    public function register(array $input)
    {
        $merchant = new Merchant\Entity;
        $error = $merchant->build($input);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $merchant->saveOrFail();

        $details = array('merchant_id' => $merchant->id);

        MerchantDetails\Entity::createOrFail($details);

        $this->queueConfirmationMail($merchant);

        $slackData = [
            'id'   => $merchant->id,
            'name'          => $merchant->name,
            'email'         => $merchant->email
        ];

        $this->slackPost('New Registration on Dashboard!', $slackData, '#sales', '@channel');

        return [$error, $slackData];
    }

    protected function queueConfirmationMail($merchant)
    {
        $merchant = $merchant->generateEmailData();

        Mail::send('emails.confirmation', compact('merchant'), function($m) use ($merchant)
        {
            $m->to($merchant['email'], $merchant['name'])->subject('Welcome to Razorpay!');
        });
    }

    public function changePassword(array $input)
    {
        $merchant = \Auth::merchant()->user();

        if ($merchant->isTestAccount()) {
            return [["Password change forbidden on this account"], null];
        }

        $error = $merchant->changePassword($input);

        if (empty($error))
        {
            $merchant->save();
        }

        return [$error, null];
    }

    public function confirm($token)
    {
        $merchant = Merchant\Entity::getMerchantForConfirmation($token);

        if ($merchant === null)
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
            return array($e->getMessage());
        }

        $merchant->confirm();
        $merchant->saveOrFail();

        return array();
    }

    public function login(array $input)
    {
        $error = (new Merchant\Validator)->validateInput('login', $input)->messages();

        if (empty($error) === false)
        {
            return [['Email or password is invalid.'], null];
        }

        $credentials = array(
            'email'     => $input['email'],
            'password'  => $input['password']
        );

        $merchant = \Auth::merchant();

        if ($merchant->validate($credentials) === false)
        {
            // Checks credentials but doesn't login the merchant, throws error if invalid
            $error = ['Email or password is invalid.'];
        }
        else if ($merchant->attempt($credentials + array('confirm_token' => null)) === false)
        {
            // Tries to login merchant if confirmed, throws error if merchant is not confirmed
            $error = ['not activated'];
        }

        return [$error, null];
    }

    public function resendConfirmation(array $input)
    {
        $error = (new Merchant\Validator)->validateInput('login', $input)->messages();

        if (empty($error))
        {
            $credentials = array(
                'email'     => $input['email'],
                'password'  => $input['password']
            );

            $merchant = \Auth::merchant();

            if ($merchant->once($credentials))
            {
                $merchant = \Auth::merchant()->get();

                if ($merchant->confirm_token === null)
                {
                    return [['Merchant already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], $data];
                }

                $this->queueConfirmationMail($merchant);

                return [[], []];
            }
        }

        return [['Email or password is invalid.'], $data];
    }

    public function fetch($merchant_id)
    {
        $merchant = Merchant\Entity::findOrFail($merchant_id)->toArray();

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
            $errors[] = $e->getMessage();
        }

        return array($errors, $data);
    }

    public function rollKeys(array $input, $mode)
    {   
        if (\Auth::merchant()->user()->isTestAccount()) {
            return [["Roll key forbidden on this account"], null];
        }

        $error = (new Merchant\Validator)->validateInput('key', $input)->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $this->setApiCredentials(null, $mode);

        $key_data = array();

        try
        {
            $response = $this->api->merchant
                                ->fetch($input['merchant_id'])
                                ->keys()
                                ->fetch($input['id'])
                                ->roll($input['delay_roll'])
                                ->toArray();

            $key_data = array(
                'old_id'        => $input['id'],
                'merchant_id'   => $input['merchant_id'],
                'new'           => $response['new']
            );
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $key_data);
    }

}
