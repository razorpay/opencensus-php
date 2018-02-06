<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{

    /**
     * Remark messages received when the transaction failed from our end.
     * Remark will start with the below string in such case
     */
    const INTERNAL_FAILURE_REMARK = 'Rejected by RTGS Gateway';

    protected function getAttemptStatus(): array
    {
        $status = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        $failureReason = null;

        if (in_array($bankStatusCode,
                [Status::PAID, Status::PENDING, Status::AWAITING], true) == true)
        {
            $status = Attempt\Status::PROCESSED;

            // This should ideally be the time this request was sent to the bank.
            // Needs to be changed to initiated_at when we have that column.
            $recordDate = Carbon::createFromTimestamp($this->fta->getCreatedAt(), Timezone::IST);

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $tenTenPm = $recordDate->hour(22)->minute(10)->getTimestamp();

            if (($now < $tenTenPm) and ($this->env !== 'testing'))
            {
                $status = $this->fta->getStatus();
            }
        }
        else if ($bankStatusCode === Status::CANCELLED)
        {
            $status = Attempt\Status::FAILED;

            $failureReason = 'Reconciliation';
        }

        return [$status, $failureReason];
    }

    protected function updateSourceEntity()
    {
        if ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId())
        {
            return;
        }

        $sourceStatus = $this->getSourceStatusFromReconEntityStatus();

        $this->source->setStatus($sourceStatus);
        $this->source->setUtr($this->fta->getUtr());
        $this->source->setRemarks($this->fta->getRemarks());
        $this->source->setFailureReason($this->fta->getFailureReason());

        $this->repo->saveOrFail($this->source);
    }

    protected function isMerchantLevelError(): bool
    {
        $remarks = $this->fta->getRemarks();

        $status  = $this->fta->getStatus();

        if (($status === Attempt\Status::FAILED) and
            (empty($remarks) === false))
        {
            return (stripos($remarks, self::INTERNAL_FAILURE_REMARK) === false);
        }

        return false;
    }
}
