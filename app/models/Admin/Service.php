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

    public function listMerchants($input)
    {
        $data = Merchant\Entity::with('merchantDetails')->get();

        if(reset($input) !== false)
        {
            list($key, $value) = each($input);
            switch($key){
                case "activated":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        return ($merchant->activated == $value);
                    });
                    break;
                case "pending":
                    $response = $data->filter(function($merchant)
                    {
                        return ($merchant->activated == 0 and $merchant->merchant_details->submitted == 1);
                    });
                    break;
                case "confirmed":
                    $response = $data->filter(function($merchant) use($value)
                    {   
                        if($value)
                            return ($merchant->confirm_token == null);
                        else
                            return !($merchant->confirm_token == null);
                    });
                    break;
                case "dead":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        if($value)
                            return ($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                        else
                            return !($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                    });
                    break;
                default:
                   $response = $data;
            }
        }
        else 
        {
            $response = $data;
        }

        $response = $response->toArray();

        return ['count'=>count($response), 'data'=>$response];
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
        $merchant = Merchant\Entity::findorfail($id);

        if($merchant->confirm_token !== Null)
        {
            return $merchant->toArray();
        }

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

    public function fetchMerchantBalance($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchBalance()->toArray();

        return $response;
    }

    public function fetchMerchantBanks($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchBanks()->toArray();

        return $response;
    }

    public function postMerchantBanks($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('banks', $input)->messages();

        $data = [];

        if (empty($error))
        {
            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->setBanks($input)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function postAddAdjustment($id, $input)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials($id);

        try
        {
            $data = $this->api->adjustment->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
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

        $live_terminals = $this->api->merchant->fetch($id)->fetchTerminals()->toArray();

        $this->setApiCredentials(null, 'test');

        $test_terminals = $this->api->merchant->fetch($id)->fetchTerminals()->toArray();

        foreach($test_terminals['items'] as &$item)
        {
            $item['mode'] = 'test';
        }

        foreach($live_terminals['items'] as &$item)
        {
            $item['mode'] = 'live';
        }

        $response = array(
            'entity'    => 'collection',
            'count'     => $live_terminals['count'] + $test_terminals['count'],
            'items'     => array_merge($live_terminals['items'], $test_terminals['items'])
        );

        return $response;
    }

    public function postMerchantTerminal($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('terminal', $input)->messages();

        $data = [];

        $mode = $input['mode'];

        if (empty($error))
        {
            unset($input['gateway_terminal_password_confirmation']);
            unset($input['mode']);

            $this->setApiCredentials(null, $mode);

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
            'ifsc_code'             => $details['merchant_details']['bank_branch_ifsc'],
            'beneficiary_name'      => $details['merchant_details']['bank_account_name'],
            'account_number'        => $details['merchant_details']['bank_account_number'],
            'beneficiary_address1'  => $details['merchant_details']['bank_beneficiary_address1'],
            'beneficiary_address2'  => $details['merchant_details']['bank_beneficiary_address2'],
            'beneficiary_address3'  => $details['merchant_details']['bank_beneficiary_address3'],
            'beneficiary_address4'  => '',
            'beneficiary_pin'       => $details['merchant_details']['bank_beneficiary_pin'],
            'beneficiary_city'      => $details['merchant_details']['bank_beneficiary_city'],
            'beneficiary_state'     => $details['merchant_details']['bank_beneficiary_state'],
            'beneficiary_country'   => 'IN',
            'beneficiary_email'     => $details['merchant_details']['contact_email'],
            'beneficiary_mobile'    => $details['merchant_details']['contact_mobile']
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

    public function fetchMultipleEntities($mode, $entity, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->admin->fetchMultipleEntities($entity, $input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if(!empty($response['items']))
        {
            $response['headings'] = array_keys($response['items'][0]);
        }
        else 
        {
            $response['headings'] = array();
        }

        return array($error, $response);
    }

    public function fetchEntityById($mode, $entity, $id)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->admin->fetchEntityById($entity, $id)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }
}