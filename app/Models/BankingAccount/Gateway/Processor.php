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

    public function preProcessAccountInfoNotification(array $input)
    {
        return;
    }

    public function processAccountInfoNotification(array $input): array
    {
        return [];
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        return [];
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        return;
    }

    public function formatInputParametersIfRequired(array $input)
    {
        return $input;
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

    /**
     * We are not rejecting requests based on the pincode availability for now.
     * This is being done to store all the leads we get for account creation.
     * Later we can choose to reject requests directly from here.
     *
     * @param string $pincode
     *
     * @return bool
     */
    protected function isPincodeServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(static::PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }
}
