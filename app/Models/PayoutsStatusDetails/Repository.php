<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = Table::PAYOUTS_STATUS_DETAILS;

    public function getPayoutStatusDetailsByPayoutId(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payouts_status_details->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
            ->where($payoutIdColumn, $payoutId)
            ->get();
    }

    public function fetchPayoutStatusDetailsLatest(string $payoutId)
    {
        $columnsToSelect = [
            Entity::REASON,
            Entity::DESCRIPTION,
        ];
        $payoutIdColumn = $this->repo->payouts_status_details->dbColumn(Entity::PAYOUT_ID);

        $createdAtColumn = $this->repo->payouts_status_details->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
            ->select($columnsToSelect)
            ->where($payoutIdColumn, $payoutId)
            ->orderBy($createdAtColumn,'desc')
            ->first();
    }
}
