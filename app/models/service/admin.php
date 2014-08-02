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

    public function fetchMerchantDetails($id)
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

    public function fetchMerchantStatus($id)
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
}