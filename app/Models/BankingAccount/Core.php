<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createBankingAccount(string $bankStatus, array $input, Merchant\Entity $merchant)
    {
        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->setStatus($bankStatus);

        $bankingAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($bankingAccount);

        $data = $bankingAccount->toArrayPublic();

        return $data;
    }

    public function getBankAvailabilityStatusForMerchant(array $input)
    {
        $bankCore = $this->getBankCore($input);

        return $bankCore->getBankAvailabilityStatus($input);
    }

    protected function getBankCore(array $input)
    {
        $bank = $input[Entity::CHANNEL];

        $class = __NAMESPACE__ . '\Bank\\' . $bank . '\Core';

        return new $class;
    }
}
