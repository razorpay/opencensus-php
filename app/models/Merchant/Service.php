<?php

namespace Models\Merchant;

use Models\Base;
use Models\Manager;
use Models\Merchant;
use Models\MerchantDetails;

class Merchant extends Base\Service
{
    public function register(array $input)
    {
        $error = (new Merchant\Entity)->build($input);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $merchant->saveOrFail();

        $details = array('merchant_id' => $merchant->id);

        MerchantDetails\Entity::createOrFail($details);

        $this->queueConfirmationMail($merchant);

        return [$error, $merchant->toArray()];
    }

    protected function queueConfirmationMail($merchant)
    {
        \Queue::push(
            'MerchantController@sendConfirmationMail',
            array('merchant' => $merchant->generateEmailData()));
    }

    public function changePassword(array $input)
    {
        $merchant = \Auth::merchant()->user();

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
            return array($e->getCode());
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

        // @todo: explain this part
        if (($merchant->attempt($credentials + array('confirm_token' => null) === false)) and
            ($merchant->validate($credentials)))
        {
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

            if (\Auth::merchant()->once($credentials))
            {
                $merchant = \Auth::merchant()->get();

                if ($merchant->confirm_token === null)
                {
                    return [['Merchant already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], $data];
                }

                $this->queueConfirmationMail($merchant);

                return [[], $data];
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
        {
            return array($error, null);
        }
    }
}
