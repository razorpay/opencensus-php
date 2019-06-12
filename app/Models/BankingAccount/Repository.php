<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function findByBankReference(string $bankReference)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_REFERENCE_NUMBER, '=', $bankReference)
                    ->firstOrFail();
    }
}
