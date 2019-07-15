<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Base;
use RZP\Models\BankingAccountStatement\Entity;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_statement';

    public function bankTransactionExists($accountNumber, $bankTxnId)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->where(Entity::BANK_TRANSACTION_ID, '=', $bankTxnId)
                    ->exists();
    }

    public function findLatestByAccountNumber($accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->latest(Entity::ID)
                    ->first();
    }
}
