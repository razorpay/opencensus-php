<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Merchant extends Service
{
    public function create(array $input)
    {
        list($merchant, $key) = Manager\Merchant::separateMerchantAndKeyCreateInput($input);

        $merchant_data = Manager\Merchant::createValidate($merchant)->getData();
        $key_data = Manager\Key::createValidate($key)->getData();

        DAL\Merchant::createOrFail($merchant_data);
        DAL\Key::createOrFail($key_data);
    }

    public function updateKey(array $input)
    {
        $old = DAL\Key::where('id','=',$input['old_id'])->first();

        if ($old === null)
            return ['status' => false];

        $time = ($input['delay_roll'] == 'true') ? 86400 : 0;
        $old->setExpired($time);

        unset($input['delay_roll']);
        unset($input['old_id']);

        try
        {
            DAL\Key::createOrFail($input);
        } catch (Exception $e)
        {
            return ['status' => false];
        }

        return ['status' => true];
    }

    public function fetchKeys($merchant_id)
    {
        return DAL\Key::getKeysForMerchant($merchant_id);
    }
}