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
                'username'     => $data['username'],
                'password'  => $input['password']
            ));

        if ($verify === true)
            return [array(), $data];
        else
            return [['Email or password is invalid.'], $data];
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

    public function listMerchants($pending = false)
    {   
        if($pending === false)
            return DAL\Merchant::get()->toArray();
        else {
            $merchants_inactive = DAL\Merchant::with('MerchantDetails')->where('activated', '=', '0')->get();

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

        if($id === \Auth::admin()->id())
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

        $response = DAL\MerchantDetails::filterDetails($merchant_details);

        $response['data'] = Manager\MerchantDetails::sortDataInSteps($response['data']);

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
        
        $request = (new Request)->setCredentials();

        $response = $request->process('GET', 'merchants/'.$id);

        //@todo This is failing tests on wercker, fix
        //Manager\Merchant::checkAPIMatch($merchant, $response);

        $response = array(
            'steps_finished'    => json_decode($merchant_details['steps_finished'], true),
            'locked'            => $merchant_details['locked'],
            'submitted'         => $merchant_details['submitted'],
            'live'              => $response['live']
        ) + $merchant->toArray();
        
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

        $response = $request->process('GET', 'merchants/'.$id.'/terminal');

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

        $response = $request->process('POST', 'merchants/'.$id.'/terminal', $input);

        if(isset($response['error']))
        {
            return array($response['error']['description']);
        }

        return array();
    }

    public function fetchMerchantPricing($id)
    {   
        $request = (new Request)->setCredentials();

        $response = $request->process('GET', 'merchants/'.$id.'/pricing');

        if(isset($response['error'])) throw new \Exception('API responded with error: '.json_encode($response['error']));

        return $response;

    }

    public function postMerchantPricing($id, $input)
    {   
        unset($input['_token']);

        $request = (new Request)->setCredentials();

        $response = $request->process('POST', 'merchants/'.$id.'/pricing', $input);

        if(isset($response['error']))
        {
            return array($response['error']['description']);
        }

        return array();
    }

    public function activateMerchant($id)
    {
        $merchant = DAL\Merchant::findorfail($id);

        $details = $this->fetchMerchantDetails($id);

        if((int)$details['submitted'] === 0)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        $request = (new Request)->setCredentials();

        $response = $request->process('POST', 'merchants/'.$id.'/activate/');

        if(isset($response['error']))
        {
            return array(json_encode($response['error']['description']));
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

        if((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $request = (new Request)->setCredentials();

        $response = $request->process('POST', 'merchants/'.$id.'/live/enable');

        if(isset($response['error']))
        {
            return array(json_encode($response['error']['description']));
        } 

        return array();
    }

    public function liveDisableMerchant($id)
    {
        $error = array();

        $merchant = DAL\Merchant::findorfail($id);

        if((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $request = (new Request)->setCredentials();

        $response = $request->process('POST', 'merchants/'.$id.'/live/disable');

        if(isset($response['error']))
        {
            return array(json_encode($response['error']['description']));
        } 

        return array();
    }

    public function fetchPricingPlan($id = NULL)
    {   
        $request = (new Request)->setCredentials();

        if($id===NULL)
        {
            $response = $request->process('GET', 'pricing/merchants');
        }
        else
        {
            $response = $request->process('GET', 'pricing/'.$id);
        }

        if(isset($response['error'])) throw new \Exception('API responded with error: '.json_encode($response['error']));

        return $response;
    }

    public function addPricingPlanRule($id, $input)
    {   
        unset($input['_token']);

        $error = array();
        
        $request = (new Request)->setCredentials();
        
        $response = $request->process('POST', 'pricing/'.$id.'/rule', $input);

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
        
        $response = $request->process('POST', 'pricing', $input);

        if(isset($response['error']))
        {
            $error[]=$response['error']['description'];
        }

        return array($error, $response);
    }
}