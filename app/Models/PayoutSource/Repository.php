<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payout_source';

    public function getPayoutSourceBySourceIdSourceTypePayoutId(string $sourceId,
                                                                string $sourceType,
                                                                string $payoutId)
    {
        $sourceIdColumn = $this->repo->payout_source->dbColumn(Entity::SOURCE_ID);

        $sourceTypeColumn = $this->repo->payout_source->dbColumn(Entity::SOURCE_TYPE);

        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
                    ->where($sourceIdColumn, $sourceId)
                    ->where($sourceTypeColumn, $sourceType)
                    ->where($payoutIdColumn, $payoutId)
                    ->first();
    }

    public function getPayoutSourceByPayoutIdAndPriority(string $payoutId, string $priority)
    {
        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        $priorityColumn = $this->repo->payout_source->dbColumn(Entity::PRIORITY);

        return $this->newQuery()
                    ->where($payoutIdColumn, $payoutId)
                    ->where($priorityColumn, $priority)
                    ->first();
    }

    public function getPayoutSourcesByPayoutId(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->where($payoutIdColumn, $payoutId)
                    ->get();
    }
}
