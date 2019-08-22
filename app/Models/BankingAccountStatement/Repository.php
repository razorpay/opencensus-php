<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_statement';

    public function bankTransactionExists($bankTxnId, $accountNumber, $bankTxnDate, $channel, $bankTxnSrlNo)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_TRANSACTION_ID, $bankTxnId)
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::TRANSACTION_DATE, $bankTxnDate)
                    ->where(Entity::BANK_SERIAL_NUMBER, $bankTxnSrlNo)
                    ->where(Entity::CHANNEL, $channel)
                    ->orderBy(Entity::ID, 'desc')
                    ->exists();
    }

    public function findLatestByAccountNumber($accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->latest(Entity::ID)
                    ->first();
    }

    public function findByAccountNumberWithInPeriod($accountNumber, $fromDate = null, $toDate = null)
    {
        # TODO: Fix this query, to incorporate fromDate and toDate
        return $this->newQuery()
            ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)->get();
    }
}
