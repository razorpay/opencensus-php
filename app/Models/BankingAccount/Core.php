<?php

namespace RZP\Models\BankingAccount;

use Redis;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestValidationFailureException;

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

    public function updateRblBankingAccount(Entity $bankingAccount, array $input): Entity
    {
        (new Validator)->validateInput('rbl_update', $input);

        $this->checkRblToInternalStatusMapping($input);

        $this->checkMerchantIsActivated($bankingAccount);

        $bankingAccount = $bankingAccount->edit($input);

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

    public function addServiceablePincodesForRbl(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->sadd(self::RBL_PINCODES_REDIS_KEY, $pincodes);
    }

    public function deleteServiceablePincodesForRbl(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->srem(self::RBL_PINCODES_REDIS_KEY, $pincodes);
    }

    protected function checkRblToInternalStatusMapping(array $input)
    {
        if (isset($input[Entity::BANK_INTERNAL_STATUS]) === false)
        {
            return;
        }

        $bankInternalStatus = $input[Entity::BANK_INTERNAL_STATUS];
        $status             = $input[Entity::STATUS];

        RblStatus::validate($bankInternalStatus);
        RblStatus::validateInternalBankStatusToStatus($bankInternalStatus, $status);
    }

    /**
     * This method is responsible for checking that unless the merchant is L2 activated, no one can update
     * the status of RBL current account to processed. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivated(Entity $bankingAccount)
    {
        $merchant = $bankingAccount->merchant;

        $merchantActivationStatus = $merchant->merchantDetail->getActivationStatus();

        if ($merchantActivationStatus !== Detail\Status::ACTIVATED)
        {
            throw new BadRequestValidationFailureException(
                'Operation not allowed, merchant is not L2 activated',
                null,
                [
                    'merchant_activation_status' => $merchant->merchantDetail->getActivationStatus(),
                    'banking_account'            => $bankingAccount->getId(),
                ]);
        }
    }
}
