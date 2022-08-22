<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'credit_transaction';

    /**
     * returns all credits logs of a transaction sorted in descending order of id
     *
     * @param string $transactionId
     * @return mixed
     */

    public function getAllCreditLogsOfTransaction(string $transactionId)
    {
        return $this->newQuery()
                    ->where(Entity::TRANSACTION_ID, '=', $transactionId)
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    public function getCreditTransactionsForSource(string $sourceId, string $sourceType)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $sourceId)
                    ->where(Entity::ENTITY_TYPE, $sourceType)
                    ->get();
    }

    public function getLatestCreditTransactionsForSource(string $sourceId, string $sourceType)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $sourceId)
            ->where(Entity::ENTITY_TYPE, $sourceType)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }

    public function getSumOfCreditTransactionsForSource(string $sourceId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $sourceId)
                    ->sum('credits_used');
    }

    public function getReverseCreditTransactionsForSource(string $sourceId, string $sourceType)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $sourceId)
                    ->where(Entity::ENTITY_TYPE, $sourceType)
                    ->where(Entity::CREDITS_USED, '<', 0)
                    ->get();
    }
}
