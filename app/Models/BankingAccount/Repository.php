<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function getBankingAccountsWithBalance($merchantId)
    {
        return $this->newQuery()
                    ->with('balance:id, balance, currency')
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }
}
