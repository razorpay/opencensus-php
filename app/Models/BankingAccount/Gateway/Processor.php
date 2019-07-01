<?php

namespace RZP\Models\BankingAccount\Gateway;

use Redis;
use RZP\Models\Base;

class Processor extends Base\Core
{
    const PINCODES_REDIS_KEY = 'pincode_set';

    public function validateAndPreProcessInputForAccountCreation(array $input)
    {
        $this->validateInputForAccountCreation($input);

        return $this->preProcessInputForAccountCreation($input);
    }

    public function addServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->sadd(static::PINCODES_REDIS_KEY, $pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->srem(static::PINCODES_REDIS_KEY, $pincodes);
    }

    protected function isPincodeServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(static::PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }
}
