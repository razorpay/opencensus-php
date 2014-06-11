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
}