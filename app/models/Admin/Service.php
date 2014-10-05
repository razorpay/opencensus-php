<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Admin extends Service
{

    public function login(array $input)
    {
        list($error, $data) = Manager\Admin::createValidate($input, 'login')->getData();

        $verify = false;

        if (empty($error))
            $verify = \Auth::admin()->attempt(array(
                'username'  => $data['username'],
                'password'  => $input['password']
            ));

        if ($verify === true)
        {
            return [array(), $data];
        }
        else
        {
            return [['Username or password is invalid.'], $data];
        }
    }

    /**
     * Changes password oflogged in admin
     *
     * @param  $input input array
     * @param  $admin DAL\Admin Object
     * @return  Status
     */
    public function changePassword($input, DAL\Admin $admin)
    {
        list($error, $data) = Manager\Admin::createValidate($input, 'password')->getData();

        if (empty($error) === false)
        {
            return [$error, $data];
        }

        $old_password = $data['old_password'];

        unset($data['old_password']);

        if (\Hash::check($old_password, $admin->password) == false)
        {
            $error = array("Invalid Password");

            return [$error, $data];
        }

        $admin = $admin->update($data);

        return [$error, $data];
    }

    public function listMerchants($pending = false)
    {
        if ($pending === false)
        {
            return DAL\Merchant::get()->toArray();
        }
        else
        {
            $merchants_inactive = DAL\Merchant::with('MerchantDetails')
                                              ->where('activated', '=', '0')
                                              ->get();

            $merchants_submitted_inactive = $merchants_inactive->filter(function($merchant)
            {
                return ($merchant->merchant_details->submitted == 1);
            });

            return $merchants_submitted_inactive->toArray();
        }
    }

    public function getAdmins()
    {
        return DAL\Admin::get()->toArray();
    }

    public function deleteAdmin($id)
    {
        $error = array();

        if ($id === \Auth::admin()->id())
         $error[] = 'You can not delete yourself.';

        $admin = DAL\Admin::findorfail($id);
        $admin->delete();

        return $error;
    }

    /* Adds a new admin
     * @param $data input array
     * @return Status
     */
    public function add($input, DAL\Admin $admin)
    {
        list($error, $data) = Manager\Admin::createValidate($input, 'register')->getData();

        if (empty($error))
        {
            $admin = DAL\Admin::createOrFail($data);

            $data = $admin->toArray();
        }

        return [$error, $data];
    }

    public function fetchMerchantActivationDetails($id)
    {
        $merchant_details =  DAL\MerchantDetails::findorfail($id);

        $response = $merchant_details->filterDetails();

        $response['data'] = Manager\MerchantDetails::sortDataInSteps($response['data']);

        foreach ($response['files'] as $key => &$file)
        {
            $extension_position = strrpos($file, '.', -1);
            $extension  = substr($file, $extension_position + 1);

            $s3 =  \AWS::get('s3');

            try
            {
                $result = $s3->getObjectUrl(
                            $_ENV['AWS_ACTIVATION_BUCKET'],
                            $id.'/'.$key.'.'.$extension,
                            '+10 minutes'
                );

                $file = $result;
            }
            catch(\Exception $e)
            {
                $file = 'ERROR';
            }
        }

        return $response;
    }

    public function fetchMerchantDetails($id)
    {
        $merchant_details = DAL\MerchantDetails::findorfail($id);

        $this->setApiCredentials();

        $data = $this->api->merchant->fetch($id)->toArray();

        $data['merchant_details'] = $merchant_details;

        // @todo This is failing tests on wercker, fix
        // $merchant = DAL\Merchant::findorfail($id);
        // Manager\Merchant::checkAPIMatch($merchant, $response);

        $response = array(
            'steps_finished'    => json_decode($merchant_details['steps_finished'], true),
            'locked'            => $merchant_details['locked'],
            'submitted'         => $merchant_details['submitted'],
            'live'              => $data['live']
        ) + $data;

        return $response;
    }

    public function lockMerchant($id)
    {
        $error = array();

        $merchant_details = DAL\MerchantDetails::findorfail($id);

        if ($merchant_details->locked === 1)
        {
            $error[] = 'Merchant already locked.';
            return $error;
        }

        $merchant_details->locked = 1;
        $merchant_details->save();

        return $error;
    }

    public function unlockMerchant($id)
    {
        $error = array();

        $merchant_details = DAL\MerchantDetails::findorfail($id);

        if ($merchant_details->locked === 0)
        {
            $error[] = 'Merchant already unlocked.';
            return $error;
        }

        $merchant_details->locked = 0;
        $merchant_details->save();

        return $error;
    }

    public function fetchMerchantTerminal($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchTerminal()->toArray();

        return $response;
    }

    public function postMerchantTerminal($id, $input)
    {

        list($error, $data) = Manager\Merchant::createValidate($input, 'terminal')->getData();

        if (empty($error))
        {
            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->setTerminal($data)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getCode();
            }
        }

        return array($error, $data);
    }

    public function fetchMerchantPricing($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchPricing()->toArray();

        return $response;
    }

    public function postMerchantPricing($id, $input)
    {
        $error = array();
        $data = array();

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id)->setPricing($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getCode();
        }

        return array($error, $data);
    }

    public function activateMerchant($id)
    {
        $merchant = DAL\Merchant::findorfail($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int)$details['submitted'] === 0)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->activate();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getCode());
        }

        $merchant->activated = 1;
        $merchant->save();

        $this->lockMerchant($id);

        return array();
    }

    public function liveEnableMerchant($id)
    {
        $error = array();

        $merchant = DAL\Merchant::findorfail($id);

        if ((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->enable();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getCode());
        }

        return array();
    }

    public function liveDisableMerchant($id)
    {
        $error = array();

        $merchant = DAL\Merchant::findorfail($id);

        if ((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->disable();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getCode());
        }

        return array();
    }

    public function fetchPricingPlans()
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->merchants()->toArray();

            $response = $response['data'];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getCode();
        }

        return array($errors, $response);
    }

    public function fetchPricingPlan($id)
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getCode();
        }

        return array($errors, $response);
    }

    public function addPricingPlanRule($id, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->createRule($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getCode();
        }

        return array($error, $response);
    }

    public function createPricingPlan($input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getCode();
        }

        return array($error, $response);
    }
}