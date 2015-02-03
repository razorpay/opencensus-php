<?php

namespace Models\Admin;

use Models\Base;
use Models\Admin;
use Models\Merchant;
use Models\MerchantDetails;

class Service extends Base\Service
{
    public function login(array $input)
    {
        $error = (new Admin\Validator)->validateInput('login', $input)->messages();

        $verify = false;

        if (empty($error))
        {
            $verify = \Auth::admin()->attempt($input);
        }

        $error = ($verify) ? [] : ['Username or password is invalid.'];

        return [$error, null];
    }

    /**
     * Changes password oflogged in admin
     *
     * @param  $input input array
     * @param  $admin Admin\Entity Object
     * @return  Status
     */
    public function changePassword($input, $admin)
    {
        $error = $admin->changePassword($input);

        if (empty($error))
        {
            $admin->saveOrFail();
        }

        return [$error, null];
    }

    public function listMerchants($pending = false)
    {
        if ($pending === false)
        {
            return Merchant\Entity::get()->toArray();
        }
        else
        {
            $merchants_inactive = Merchant\Entity::with('MerchantDetails')
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
        return Admin\Entity::get()->toArray();
    }

    public function deleteAdmin($id)
    {
        $error = array();

        if ($id === \Auth::admin()->id())
        {
            $error[] = 'You can not delete yourself.';
        }

        $admin = Admin\Entity::findorfail($id);

        $admin->delete();

        return $error;
    }

    /* Adds a new admin
     * @param $data input array
     * @return Status
     */
    public function add($input)
    {
        $admin = new Admin\Entity;
        $error = $admin->build($input);

        if (empty($error))
        {
            $admin->saveOrFail();
        }

        return [$error, $admin->toArray()];
    }

    public function fetchMerchantActivationDetails($id)
    {
        $merchant_details =  MerchantDetails\Entity::findorfail($id);

        $response = $merchant_details->filterDetails();

        $response['data'] = MerchantDetails\Validator::sortDataInSteps($response['data']);

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
        $merchant_details = MerchantDetails\Entity::findorfail($id);

        $this->setApiCredentials();

        $data = $this->api->merchant->fetch($id)->toArray();

        $data['merchant_details'] = $merchant_details->toArray();

        // @todo This is failing tests on wercker, fix
        // $merchant = Merchant\Entity::findorfail($id);
        // Merchant\Validator::checkAPIMatch($merchant, $response);

        $response = array(
            'steps_finished'    => $merchant_details['steps_finished'],
            'locked'            => $merchant_details['locked'],
            'submitted'         => $merchant_details['submitted'],
            'live'              => $data['live']
        ) + $data;

        return $response;
    }

    public function fetchMerchantBanks($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchBanks()->toArray();

        return $response;
    }

    public function lockMerchant($id)
    {
        $error = array();

        $merchant_details = MerchantDetails\Entity::findorfail($id);

        if ($merchant_details->isLocked())
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

        $merchant_details = MerchantDetails\Entity::findorfail($id);

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

        $response = $this->api->merchant->fetch($id)->fetchTerminals()->toArray();

        return $response;
    }

    public function postMerchantTerminal($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('terminal', $input)->messages();

        $data = [];

        $input['gateway'] = strtolower($input['gateway']);

        if (empty($error))
        {
            unset($input['gateway_terminal_password_confirmation']);

            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->setTerminal($input)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
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
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function activateMerchant($id)
    {
        $merchant = Merchant\Entity::findorfail($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int)$details['submitted'] === 0)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        $this->setApiCredentials();

        $bankAccount = array(
            'ifsc_code'         => $details['merchant_details']['bank_branch_ifsc'],
            'beneficiary_name'  => $details['merchant_details']['bank_account_name'],
            'account_number'    => $details['merchant_details']['bank_account_number']
        );

        try
        {
            $this->api->merchant->fetch($id)->setBankAccount($bankAccount);

            $this->api->merchant->fetch($id)->activate();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        $merchant->activated = 1;
        $merchant->save();

        $this->lockMerchant($id);

        return array();
    }

    public function liveEnableMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if ((int)$merchant->activated === 0)
        {
            return array(
                'Merchant must be active before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->enable();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function liveDisableMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

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
            return array($e->getMessage());
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

            $response = $response['items'];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
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
            $errors[] = $e->getMessage();
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
            $error[] = $e->getMessage();
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
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }
}