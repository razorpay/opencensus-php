<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createBankingAccount(array $input, Merchant\Entity $merchant)
    {
        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->setStatus(Status::CREATED);

        $bankingAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($bankingAccount);

        $data = $bankingAccount->toArrayPublic();

        return $data;
    }

    public function validateBankAvailabilityForMerchant(array $input)
    {
        $bankCore = $this->getBankCore($input);

        $bankCore->validateAvailability($input);
    }

    protected function getBankCore(array $input)
    {
        $bank = $input[Entity::BANK];

        $class = __NAMESPACE__ . '\Bank\\' . $bank . '\Core';

        return new $class;
    }
}
