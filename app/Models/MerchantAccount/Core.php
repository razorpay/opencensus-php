<?php

namespace RZP\Models\MerchantAccount;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function createMerchantAccount(array $input)
    {
        $merchantAccount = new Entity();

        $merchantAccount->build($input);

        $merchantAccount->setStatus(Constant::CREATED);

        $data = $merchantAccount->toArrayPublic();

        return $data;
    }

    public function validateBankAccountForMerchant(array $input)
    {
        $bankCore = $this->getBankCore($input);

        $bankCore->validateBankAccountForMerchant($input);
    }

    protected function getBankCore(array $input)
    {
        $bank = $input[Entity::BANK];

        $class = __NAMESPACE__ . '\Bank\\' . $bank . '\Core';

        return new $class;
    }
}
