<?php

namespace RZP\Models\BankingAccount;

use Razorpay\IFSC\Bank;
use Redis;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const RBL_PINCODES_REDIS_KEY = 'rbl_pincode_set';

    public function createBasicBankingAccountFromVA(VirtualAccount\Entity $virtualAccount): Entity
    {
        if ($virtualAccount->hasBankAccount() === false)
        {
            throw new LogicException(
                'Banking accounts can only be create on bank type virtual accounts',
                null,
                ['virtual_account_id' => $virtualAccount->getId()]);
        }

        $bankAccount = $virtualAccount->bankAccount;
        $bankCode    = $bankAccount->getBankCode();

        if ($bankCode !== Bank::YESB)
        {
            throw new LogicException(
                'Only YesBank virtual accounts are supported', // for now 🤑
                null,
                ['bank_code' => $bankCode]);
        }

        $bankingAccountInput = [
            Entity::ACCOUNT_IFSC        => $bankAccount->getIfscCode(),
            Entity::ACCOUNT_NUMBER      => $bankAccount->getAccountNumber(),
            Entity::BALANCE_ID          => $virtualAccount->getBalanceId(),
            Entity::FTS_FUND_ACCOUNT_ID => $bankAccount->getFtsFundAccountId(),
        ];

        return $this->createYesbankBankingAccount($bankingAccountInput, $virtualAccount->merchant);
    }

    public function createRblBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        // TODO: Validate if account does not already exist for the merchant

        $status = $this->getRblAvailabilityStatus($input);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->setStatus($status);

        // TODO: Fix this logic
        $refNumber = substr(time(), 0, 5);

        $bankingAccount->setBankReferenceNumber($refNumber);

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

    protected function createYesbankBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'channel' => Channel::YESBANK,
                'input'   => $input,
            ]);

        $input[Entity::CHANNEL] = Channel::YESBANK;

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        // Yesbank accounts are always created in the processed state
        $bankingAccount->setStatus(Status::PROCESSED);

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
