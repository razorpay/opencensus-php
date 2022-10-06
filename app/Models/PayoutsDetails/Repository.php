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

    public function getPayoutDetailsByPayoutIds(array $payoutIds)
    {
        $payoutIdColumn = $this->repo->payouts_details->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
            ->whereIn($payoutIdColumn, $payoutIds)
            ->get();
    }

    public function updatePayoutDetails(array $payoutIds, $updates)
    {
        return $this->newQuery()
                    ->whereIn(Entity::PAYOUT_ID, $payoutIds)
                    ->update($updates);
    }

    public function getPayoutServicePayoutDetails(string $payoutId)
    {
        $tableName = 'payout_details';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where payout_id = '$payoutId'");
    }
}
