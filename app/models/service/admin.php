<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Admin extends Service
{

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

        if(empty($error) === false)
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

    public function listMerchants()
    {
        return DAL\Merchant::get()->toArray();
    }

    public function getAdmins()
    {
        return DAL\Admin::get()->toArray();
    }

    public function deleteAdmin($id)
    {
        if($id === \Auth::admin()->id()) return false;
        try
        {
            $admin = DAL\Admin::findorfail($id);
            $admin->delete();
        }
        catch(\Exception $e)
        {
            return false;
        }
        return true;
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

        $response = DAL\MerchantDetails::filterDetails($merchant_details);

        foreach($response['files'] as $key => &$file)
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
        $merchant = DAL\Merchant::with('MerchantDetails')->findorfail($id);
        
        $merchant_details =  $merchant->MerchantDetails;

        $response = array(
            'steps_finished'    => json_decode($merchant_details['steps_finished'], true),
            'locked'            => $merchant_details['locked'],
            'merchant'          => $merchant->toArray()
        );
        
        return $response;
    }

    public function lockMerchant($id)
    {   
        $error = array();

        $merchant_details = DAL\MerchantDetails::findorfail($id);

        if($merchant_details->locked === 1)
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

        if($merchant_details->locked === 0)
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
        $request = (new Request)->setCredentials();

        $response = $request->GET('merchants/'.$id.'/terminal');

        if(isset($response['error'])) throw new \Exception('API responded with error: '.json_encode($response['error']));

        return $response;
    }

    public function postMerchantTerminal($id, $input)
    {   
        if($input['gateway_terminal_password'] !== $input['gateway_terminal_password_confirmation'])
        {
            return array('Password do not match');
        }

        unset($input['_token']);
        unset($input['gateway_terminal_password_confirmation']);

        $request = (new Request)->setCredentials();

        $response = $request->POST('merchants/'.$id.'/terminal', $input);

        if(isset($response['error']))
        {
            return array($response['error']['description']);
        }

        return array();
    }

    public function fetchMerchantPricing($id)
    {   
        $request = (new Request)->setCredentials();

        $response = $request->GET('merchants/'.$id.'/pricing');

        if(isset($response['error'])) throw new \Exception('API responded with error: '.json_encode($response['error']));

        return $response;

    }

    public function postMerchantPricing($id, $input)
    {   
        unset($input['_token']);

        $request = (new Request)->setCredentials();

        $response = $request->POST('merchants/'.$id.'/pricing', $input);

        if(isset($response['error']))
        {
            return array($response['error']['description']);
        }

        return array();
    }

    public function activateMerchant($id)
    {
        $merchant = DAL\Merchant::findorfail($id);

        $details = $this->fetchMerchantStatus($id);

        if(in_array(5, $details['steps_finished'])===false)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        if((int)$merchant->live === 1)
        {
            return array('Merchant is already active.');
        }
        
        if(empty($this->fetchMerchantPricing($id)))
        {
            return array('Merchant must be assigned a pricing plan before he goes live');
        }

        if(empty($this->fetchMerchantTerminal($id)))
        {
            return array('Merchant must be assigned a gateway terminal before he goes live');
        }

        //@todo mark merchant live in api first
        
        $merchant->live = 1;
        $merchant->save();

        $this->lockMerchant($id);
    
        return array();
    }

    public function deactivateMerchant($id)
    {
        $merchant = DAL\Merchant::findorfail($id);

        $details = $this->fetchMerchantStatus($id);

        if((int)$merchant->live === 0)
        {
            return array('Merchant is already inactive.');
        }

        //@todo mark merchant inactive in api first
            
        $merchant->live = 0;
        $merchant->save();  
        
        return array();
    }

    public function fetchPricingPlan($id = NULL)
    {   
        $request = (new Request)->setCredentials();

        if($id===NULL)
        {
            $response = $request->GET('pricing/merchants');
        }
        else
        {
            $response = $request->GET('pricing/'.$id);
        }

        if(isset($response['error'])) throw new \Exception('API responded with error: '.json_encode($response['error']));

        return $response;
    }

    public function addPricingPlanRule($id, $input)
    {   
        unset($input['_token']);

        $error = array();
        
        $request = (new Request)->setCredentials();
        
        $response = $request->POST('pricing/'.$id.'/rule', $input);

        if(isset($response['error']))
        {
            $error[]=$response['error']['description'];
        }

        return $error;
    }

    public function createPricingPlan($input)
    {
        unset($input['_token']);

        $error = array();
        
        $request = (new Request)->setCredentials();
        
        $response = $request->POST('pricing', $input);

        return $response;
    }
}