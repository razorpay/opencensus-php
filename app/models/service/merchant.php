<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Merchant extends Service
{
    public function create(array $input)
    {
        $merchantId['id'] = $input['merchant_id'];

        $merchantData = Manager\Merchant::createValidate($merchantId)->getData();

        $keyData = Manager\Key::createValidate($key)->getData();

        DAL\Merchant::createOrFail($merchantData);

        DAL\Key::createOrFail($keyData);
    }

    public function updateKey(array $input)
    {
        $old = DAL\Key::find($input['old_id']);

        if ($old === null)
        {
            return ['status' => false];
        }

        // @todo: remove the magic number
        $time = ($input['delay_roll'] == 'true') ? 86400 : 0;

        $old->setExpired($time);
        $old->save();

        unset($input['delay_roll']);
        unset($input['old_id']);

        try
        {
            DAL\Key::createOrFail($input);
        }
        catch (Exception $e)
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