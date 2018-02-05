<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function getAttemptStatus(): array
    {
        //TODO: Attempt status based on the status given by bank.
        $status = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        if(Status::SETTLED === $bankStatusCode)
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

        return [$status, null];
    }

    protected function updateSourceEntity()
    {
        if ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId())
        {
            return;
        }

        $sourceStatus = $this->getSourceStatusFromReconEntityStatus();

        $this->source->setUtr($this->fta->getUtr());

        $this->source->setRemarks($this->fta->getRemarks());

//      TODO: uncomment to update the source status once the bank confirms the possible status of recon
//        $this->source->setStatus($sourceStatus);

//        $this->source->setFailureReason($this->fta->getFailureReason());

        $this->repo->saveOrFail($this->source);
    }

    protected function isMerchantLevelError(): bool
    {
        return false;
    }
}
