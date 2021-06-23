<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = Table::PAYOUTS_DETAILS;

    public function getPayoutDetailsByPayoutId(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payouts_details->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
                    ->where($payoutIdColumn, $payoutId)
                    ->get();
    }

}
