<?php

namespace RZP\Models\BankingAccount\State;

use Closure;
use Illuminate\Support\Facades\DB;
use RZP\Base;
use RZP\Models\BankingAccount\Status;
use RZP\Trace\TraceCode;

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

    public function getFirstStatusChangeLog(string $bankingAccountId, string $status, string $substatus = null)
    {
        $query = $this->newQuery()
            ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
            ->where(Entity::STATUS, $status);

        if ($substatus)
        {
            $query->where(Entity::SUB_STATUS, $substatus);
        }

        return $query->first();
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
     * Get all created_at for a specific status
     *
     * @param $bankingAccountIds
     * @param $status
     */
    public function getStateChangeLogForMultipleBankingAccounts(array $bankingAccountIds, string $status, string $orderBy = 'asc')
    {
        $startTime = microtime(true);

        $stateBankingAccountIdCol = $this->repo->banking_account_state->dbColumn(Entity::BANKING_ACCOUNT_ID);
        $stateStatusCol = $this->repo->banking_account_state->dbColumn(Entity::STATUS);
        $stateCreatedAtCol = $this->repo->banking_account_state->dbColumn(Entity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($stateBankingAccountIdCol, $stateCreatedAtCol)
            ->whereIn($stateBankingAccountIdCol, $bankingAccountIds)
            ->where($stateStatusCol, $status)
            ->orderBy($stateCreatedAtCol, $orderBy);

        $timestamps = $query->get();

        $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_DB_QUERY_DURATION, [
            'query'       => 'state_timestamps',
            'status'      => $status,
            'count'       => count($bankingAccountIds),
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);

        return $timestamps;

    }

}
