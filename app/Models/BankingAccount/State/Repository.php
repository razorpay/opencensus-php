<?php

namespace RZP\Models\BankingAccount\State;

use Closure;
use Illuminate\Support\Facades\DB;
use RZP\Base;
use RZP\Models\BankingAccount\Status;

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

    public function getAnySendToBankStateByBankingAccountId(string $bankingAccountId)
    {
        return $this->newQuery()
            ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
            ->where(Entity::STATUS, Status::INITIATED)
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

    public function getBankingAccountsStateByUserIds(string $bankingAccountId, array $userIds, array $expands = ['user'])
    {
        return $this->newQuery()
                    ->with($expands)
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
                    ->whereIn(Entity::USER_ID, $userIds)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    /**
     * Given an array for bankingAccountIds  
     * Aggregate using created_at in a specific order grouping by banking_account_id  
     * Join with the same table with Subquery to filter a specific status change log
     * 
     * @param $bankingAccountIds
     * @param $order
     * @param $attributes
     * @param $modifyQuery
     */
    public function getStateChangeLogForMultipleBankingAccounts($bankingAccountIds, string $order = 'last', $attributes = [], Closure $modifyQuery = null)
    {
        $aggregationFunc = 'MAX';

        if ($order === 'first') {
            $aggregationFunc = 'MIN';
        }

        $query = $this->newQuery()
            ->select(DB::raw(Entity::BANKING_ACCOUNT_ID.' as banking_account_id, '.$aggregationFunc.'(created_at) as created_at'))
            ->whereIn(Entity::BANKING_ACCOUNT_ID, $bankingAccountIds);

        foreach ($attributes as $key => $value)
        {
            $query->where($key, '=', $value);
        }

        $query->groupBy(Entity::BANKING_ACCOUNT_ID);

        if ($modifyQuery != null && $modifyQuery instanceof Closure)
        {
            $modifyQuery($query);
        }
    
        return $query->get();
    }

}
