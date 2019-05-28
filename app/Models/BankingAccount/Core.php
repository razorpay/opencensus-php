<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function createBankingAccount(array $input)
    {
        $merchantAccount = new Entity();

        $merchantAccount->build($input);

        $merchantAccount->setStatus(Status::CREATED);

        $this->repo->saveOrFail($merchantAccount);

        $data = $merchantAccount->toArrayPublic();

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
