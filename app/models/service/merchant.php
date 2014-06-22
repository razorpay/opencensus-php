<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Merchant extends Service
{
    public function create(array $input)
    {
        $merchantId['id'] = $input['merchant_id'];

        $merchant_data = Manager\Merchant::createValidate($merchantId)->getData();

        $key_data = Manager\Key::createValidate($key)->getData();

        DAL\Merchant::createOrFail($merchant_data);
        DAL\Key::createOrFail($key_data);
    }

    public function updateKey(array $input)
    {
        $old = DAL\Key::find($input['old_id']);

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

    public function fetchKeys($merchantId)
    {
        return DAL\Key::getKeysForMerchant($merchantId);
    }
}