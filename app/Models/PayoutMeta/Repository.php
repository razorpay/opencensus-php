<?php

namespace RZP\Models\PayoutMeta;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = Table::PAYOUTS_META;

    public function getPayoutMetaByPayoutIdPartnerId(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payouts_meta->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
                    ->where($payoutIdColumn, $payoutId)
                    ->first();
    }

}
