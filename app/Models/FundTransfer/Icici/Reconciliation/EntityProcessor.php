<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function getAttemptStatus(): string
    {
        $status = Attempt\Status::FAILED;

        $bankStatusCode = $this->fta->getBankStatusCode();

        switch ($bankStatusCode)
        {
            case Status::PAID:
                $status = Attempt\Status::PROCESSED;
                break;

            case Status::CANCELLED:
                $status = Attempt\Status::FAILED;
                break;

            case Status::AWAITING:
                $status = $this->fta->getStatus();
                break;
        }

        return $status;
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
        return false;
    }
}