<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = Table::PAYOUTS_STATUS_DETAILS;

    public function fetchStatusReasonFromStatusDetailsId(string $id)
    {
        $idColumn = $this->repo->payouts_status_details->dbColumn(Entity::ID);

        $result =  $this->newQueryWithConnection($this->getReportingReplicaConnection())
                        ->select(Entity::REASON)
                        ->where($idColumn,$id)
                        ->first();

        if($result !== null)
        {
            return $result;
        }

        else
        {
            return $this->findOrFailOnMaster($id,Entity::REASON);
        }
    }

    public function fetchStatusDetailsFromStatusDetailsId(string $statusDetailsId)
    {
        $idColumn = $this->repo->payouts_status_details->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->select(Table::PAYOUTS_STATUS_DETAILS . '.*')
                    ->where($idColumn, $statusDetailsId)
                    ->first();
    }

    public function fetchPayoutStatusDetailsLatest(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payouts_status_details->dbColumn(Entity::PAYOUT_ID);
        $createdAtColumn = $this->repo->payouts_status_details->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
                    ->select(Table::PAYOUTS_STATUS_DETAILS.'.*')
                    ->where($payoutIdColumn, $payoutId)
                    ->orderBy($createdAtColumn,'desc')
                    ->first();
    }

    public function fetchPayoutStatusDetailsByPayoutId(string $payoutId)
    {
        $payoutIdColumn  = $this->repo->payouts_status_details->dbColumn(Entity::PAYOUT_ID);
        $idColumn = $this->repo->payouts_status_details->dbColumn(Entity::ID);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->select(Table::PAYOUTS_STATUS_DETAILS.'.*')
                    ->where($payoutIdColumn, $payoutId)
                    ->orderBy($idColumn,'desc')
                    ->get();

    }

}
