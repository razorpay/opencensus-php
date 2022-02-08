<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_activation_detail';

    public function findByBankingAccountId(string $bankingAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
                    ->first();
    }

    public function fetchRblApplicationsBySalesTeamCreatedAtNotSubmitted($salesTeam, $timestamp, $lastDuration)
    {
        return $this->newQuery()
                    ->where(Entity::SALES_TEAM, '=', $salesTeam)
                    ->where(Entity::CREATED_AT, '>', $timestamp)
                    ->where(Entity::CREATED_AT, '<', $lastDuration)
                    ->where(function ($query)
                        {
                             $query->where(Entity::DECLARATION_STEP, '=', 0)
                                   ->orWhereNull(Entity::DECLARATION_STEP);
                        })
                    ->get();
    }
}
