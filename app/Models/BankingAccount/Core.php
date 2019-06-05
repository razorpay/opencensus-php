<?php

namespace RZP\Models\BankingAccount;

use Redis;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    const RBL_PINCODES_REDIS_KEY = 'rbl_pincode_set';

    public function createRblBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        // TODO: Validate if account does not already exist for the merchant

        $status = $this->getRblAvailabilityStatus($input);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->setStatus($status);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    protected function getRblAvailabilityStatus(array $input): string
    {
        (new Validator)->validateInput('rbl_availability', $input);

        $isServiceable = $this->isPincodeRblServiceable($input[Entity::PINCODE]);

        $status = ($isServiceable === true) ? Status::CREATED : Status::UNSERVICEABLE;

        return $status;
    }

    protected function isPincodeRblServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(self::RBL_PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }

    public function addServiceablePincodesForRbl(array $pincode) : bool
    {
        $this->redisConnection()->sadd(self::RBL_PINCODES_REDIS_KEY, $pincode);

        return true;
    }

    public function deleteServiceablePincodesForRbl(array $pincode) : bool
    {
        $this->redisConnection()->srem(self::RBL_PINCODES_REDIS_KEY, $pincode);

        return true;
    }

    public function redisConnection()
    {
        return Redis::connection();
    }
}
