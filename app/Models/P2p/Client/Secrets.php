<?php

namespace RZP\Models\P2p\Client;

use Illuminate\Support\Facades\Crypt;

class Secrets extends ArrayAttribute
{
    const PRIVATE_KEY              = 'private_key';
    const API_KEY                  = 'api_key';
    const GATEWAY_MERCHANT_ID      = 'gateway_merchant_id';
    const GATEWAY_MERCHANT_ID2     = 'gateway_merchant_id2';

    protected $map = [
        self::PRIVATE_KEY           => 1,
        self::API_KEY               => 1,
        self::GATEWAY_MERCHANT_ID   => 1,
        self::GATEWAY_MERCHANT_ID2  => 1,
    ];

    public function encrypt()
    {
        return $this->map(function ($value, $key)
        {
            return Crypt::encrypt($value);
        });
    }

    public function decrypted(string $key)
    {
        $value = $this->get($key, null);

        if (isset($value) === true)
        {
            return Crypt::decrypt($value);
        }

        return null;
    }

    public function toArrayDecrypted()
    {
        $decrypted = $this->map(function ($value)
        {
           return Crypt::decrypt($value);
        });

        return $decrypted->toArray();
    }
}
