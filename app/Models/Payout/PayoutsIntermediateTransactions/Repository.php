<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS;

    const FETCH_LIMIT = 5000;

    public function fetchPendingTransactionsBeforeGivenTime($time)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::STATUS, "=", Status::PENDING)
                    ->where(Entity::PENDING_AT, "<", $time)
                    ->limit(self::FETCH_LIMIT)
                    ->get();
    }

    public function fetchIntermediateTransactionForAGivenPayoutId(string $payoutId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYOUT_ID, "=", $payoutId)
                    ->first();
    }
}
