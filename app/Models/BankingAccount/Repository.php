<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function getFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->first();
    }
}
