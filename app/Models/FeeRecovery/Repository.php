<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'fee_recovery';

    /**
     * Used after creation of fee_recovery payout. Function updates the status, attempt number and recovery_payout_id
     * so that we can map which fee_recovery payout was made for a list of payoutIds/reversalIds
     *
     * @param $entityIdList
     * @param $entityType
     * @param $type
     * @param $feeRecoveryPayoutId
     * @param $status
     * @param $currentAttemptNumber
     *
     * @return mixed
     */
    public function updateBulkStatusAndRecoveryPayoutId($entityIdList,
                                                        $entityType,
                                                        $type,
                                                        $feeRecoveryPayoutId,
                                                        $status,
                                                        $currentAttemptNumber)
    {
        $dataToUpdate = [
            Entity::RECOVERY_PAYOUT_ID  => $feeRecoveryPayoutId,
            Entity::STATUS              => $status,
            Entity::ATTEMPT_NUMBER      => $currentAttemptNumber + 1,
        ];

        $typeColumn             = $this->dbColumn(Entity::TYPE);
        $entityIdColumn         = $this->dbColumn(Entity::ENTITY_ID);
        $entityTypeColumn       = $this->dbColumn(Entity::ENTITY_TYPE);
        $attemptNumberColumn    = $this->dbColumn(Entity::ATTEMPT_NUMBER);

        return $this->newQuery()
                    ->whereIn($entityIdColumn, $entityIdList)
                    ->where($entityTypeColumn, '=', $entityType)
                    ->where($attemptNumberColumn, '=', $currentAttemptNumber)
                    ->where($typeColumn, '=', $type)
                    ->update($dataToUpdate);
    }

    public function fetchUnrecoveredFeeRecoveryCount($entityIdList,
                                                     $entityType,
                                                     $type)
    {
        $typeColumn             = $this->dbColumn(Entity::TYPE);
        $statusColumn           = $this->dbColumn(Entity::STATUS);
        $entityIdColumn         = $this->dbColumn(Entity::ENTITY_ID);
        $entityTypeColumn       = $this->dbColumn(Entity::ENTITY_TYPE);
        $attemptNumberColumn    = $this->dbColumn(Entity::ATTEMPT_NUMBER);

        return $this->newQuery()
                    ->whereIn($entityIdColumn, $entityIdList)
                    ->where($entityTypeColumn, '=', $entityType)
                    ->where($statusColumn, '=', Status::UNRECOVERED)
                    ->where($attemptNumberColumn, '=', 0)
                    ->where($typeColumn, '=', $type)
                    ->count();
    }

    /**
     * This function updates the fee_recovery status from processing to recovered/unrecovered
     * based on corresponding fee recovery payout's status.
     *
     * Eg :   ID  |   Status     | Entity ID | Attempt Number | Recovery Payout ID
     *        FR1 | PROCESSING   |     P1    |      1         |     P3
     *        FR2 | PROCESSING   |     P2    |      1         |     P3
     *        FR3 | UNRECOVERED  |     P3    |      0         |     null
     *
     * If payout corresponding to FR3 (P3) fails/succeeds, we shall update status of FR1 and FR2
     * as either recovered or unrecovered based on the status of payout P3
     *
     * Current status can be either processing or recovered based on payout status transitions
     *      1. Initiated -> Reversed
     *      2. Processed -> Reversed
     *
     * @param $recoveryPayoutId
     * @param $status
     *
     * @return mixed
     */
    public function updateFeeRecoveryOnPayoutStatusUpdate($recoveryPayoutId, $status)
    {
        $statusColumn           = $this->dbColumn(Entity::STATUS);
        $recoveryPayoutIdColumn = $this->dbColumn(Entity::RECOVERY_PAYOUT_ID);

        $dataToUpdate = [Entity::STATUS => $status];

        return $this->newQuery()
                    ->where($recoveryPayoutIdColumn, '=', $recoveryPayoutId)
                    ->whereIn($statusColumn, [Status::PROCESSING, Status::RECOVERED])
                    ->update($dataToUpdate);
    }

    public function getFeeRecoveryByRecoveryPayoutId($recoveryPayoutId, $manualRetry = false)
    {
        $statusColumn           = $this->dbColumn(Entity::STATUS);
        $recoveryPayoutIdColumn = $this->dbColumn(Entity::RECOVERY_PAYOUT_ID);
        $attemptNumberColumn    = $this->dbColumn(Entity::ATTEMPT_NUMBER);

        $query = $this->newQuery()
                      ->where($recoveryPayoutIdColumn, '=', $recoveryPayoutId)
                      ->where($statusColumn, Status::UNRECOVERED);

        if ($manualRetry === false)
        {
            $query
                ->where($attemptNumberColumn, '<', Entity::AUTOMATIC_FEE_RECOVERY_MAX_ATTEMPT_NUMBER);

        }

        return $query->get();
    }

    public function getLastUnrecoveredFeeRecoveryEntityByEntityIdType($entityId, $entityType, $type)
    {
        $typeColumn             = $this->dbColumn(Entity::TYPE);
        $statusColumn           = $this->dbColumn(Entity::STATUS);
        $entityIdColumn         = $this->dbColumn(Entity::ENTITY_ID);
        $entityTypeColumn       = $this->dbColumn(Entity::ENTITY_TYPE);
        $attemptNumberColumn    = $this->dbColumn(Entity::ATTEMPT_NUMBER);

        return $this->newQuery()
                    ->where($entityIdColumn, '=', $entityId)
                    ->where($entityTypeColumn, '=', $entityType)
                    ->where($statusColumn, '=', Status::UNRECOVERED)
                    ->where($typeColumn, '=', $type)
                    ->first();
    }

    public function fetchUnrecoveredAmountForPayouts(string $merchantId, string $balanceId)
    {
        // payout columns
        $balanceIdColumn  = $this->repo->payout->dbColumn(Payout\Entity::BALANCE_ID);
        $feesColumn       = $this->repo->payout->dbColumn(Payout\Entity::FEES);
        $idColumn         = $this->repo->payout->dbColumn(Payout\Entity::ID);
        $merchantIdColumn = $this->repo->payout->dbColumn(Payout\Entity::MERCHANT_ID);

        // fee recovery columns
        $entityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
        $statusColumn   = $this->dbColumn(Entity::STATUS);
        $typeColumn     = $this->dbColumn(Entity::TYPE);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(
                        'SUM(' . $feesColumn .') AS fees')
                    ->join(Table::PAYOUT, $idColumn, '=', $entityIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($balanceIdColumn, '=', $balanceId)
                    ->whereIn($statusColumn, [Status::UNRECOVERED, Status::PROCESSING])
                    ->where($typeColumn, '=', Type::DEBIT)
                    ->first();
    }

    public function fetchUnrecoveredAmountForFailedPayouts(string $merchantId, string $balanceId)
    {
        // payout columns
        $balanceIdColumn  = $this->repo->payout->dbColumn(Payout\Entity::BALANCE_ID);
        $feesColumn       = $this->repo->payout->dbColumn(Payout\Entity::FEES);
        $idColumn         = $this->repo->payout->dbColumn(Payout\Entity::ID);
        $merchantIdColumn = $this->repo->payout->dbColumn(Payout\Entity::MERCHANT_ID);

        // fee recovery columns
        $entityIdColumn   = $this->dbColumn(Entity::ENTITY_ID);
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $typeColumn       = $this->dbColumn(Entity::TYPE);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(
                        'SUM(' . $feesColumn .') AS fees')
                    ->join(Table::PAYOUT, $idColumn, '=', $entityIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($balanceIdColumn, '=', $balanceId)
                    ->whereIn($statusColumn, [Status::UNRECOVERED, Status::PROCESSING])
                    ->where($typeColumn, '=', Type::CREDIT)
                    ->first();
    }

    public function fetchUnrecoveredAmountForReversals(string $merchantId, string $balanceId)
    {
        // reversal columns
        $balanceIdColumn        = $this->repo->reversal->dbColumn(Reversal\Entity::BALANCE_ID);
        $reversalIdColumn       = $this->repo->reversal->dbColumn(Reversal\Entity::ID);
        $merchantIdColumn       = $this->repo->reversal->dbColumn(Reversal\Entity::MERCHANT_ID);
        $reversalEntityIdColumn = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_ID);

        // payout columns
        $feesColumn       = $this->repo->payout->dbColumn(Payout\Entity::FEES);
        $payoutIdColumn   = $this->repo->payout->dbColumn(Payout\Entity::ID);

        // fee recovery columns
        $entityIdColumn   = $this->dbColumn(Entity::ENTITY_ID);
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $typeColumn       = $this->dbColumn(Entity::TYPE);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(
                        'SUM(' . $feesColumn .') AS fees')
                    ->join(Table::REVERSAL, $reversalIdColumn, '=', $entityIdColumn)
                    ->join(Table::PAYOUT, $reversalEntityIdColumn, '=', $payoutIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($balanceIdColumn, '=', $balanceId)
                    ->whereIn($statusColumn, [Status::UNRECOVERED, Status::PROCESSING])
                    ->where($typeColumn, '=', Type::CREDIT)
                    ->first();
    }
}
