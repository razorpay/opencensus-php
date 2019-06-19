<?php

namespace RZP\Models\BankingAccount\Gateway\Base;

use Redis;

class Processor
{
    protected function isPincodeServiceable(string $pincode, string $key): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember($key, $pincode);

        return (bool) $isAvailable;
    }

    public function addPincodes(array $pincodes, string $key)
    {
        $redis = Redis::connection();

        $redis->sadd($key, $pincodes);
    }

    public function deletePincodes(array $pincodes, string $key)
    {
        $redis = Redis::connection();

        $redis->srem($key, $pincodes);
    }
}
