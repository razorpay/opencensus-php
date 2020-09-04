<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payout_source';

    public function getPayoutSourceByPayoutIdAndPriority(string $payoutId, string $priority)
    {
        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        $priorityColumn = $this->repo->payout_source->dbColumn(Entity::PRIORITY);

        return $this->newQuery()
                    ->where($payoutIdColumn, $payoutId)
                    ->where($priorityColumn, $priority)
                    ->first();
    }
}
