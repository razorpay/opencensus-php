<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_state';

    public function getStateByBankingAccountIdAndSubState(string $bankingAccountId, string $subStatus)
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
                    ->where(Entity::SUB_STATUS, '=', $subStatus)
                    ->get()
                    ->first();
    }

    public function getBankingAccountsStateBySubStateAndCreatedBetween(string $subStatus, string $from, string $to)
    {
        $data = $this->newQuery()
                     ->select(Entity::BANKING_ACCOUNT_ID, Entity::STATUS, Entity::SUB_STATUS)
                     ->distinct()
                     ->where(Entity::SUB_STATUS, '=', $subStatus)
                     ->where(Entity::CREATED_AT, '>', $from)
                     ->where(Entity::CREATED_AT, '<', $to)
                     ->get();

        return $data->groupBy(
            function($item, $key) {
                return $item->bankingAccount->spocs()->first()['email'] ?? null;
            }
        );
    }

    public function getLatestStateLogByBankingAccountId(string $bankingAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get()
                    ->first();
    }
}
