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
        s($bankStatusCode);
        switch ($bankStatusCode)
        {
            case Status::PAID:
                s('1');
                $status = Attempt\Status::PROCESSED;
                break;

            case Status::CANCELLED:
                s('2');
                $status = Attempt\Status::FAILED;
                break;

            case Status::AWAITING:
                s('3');
                $status = $this->fta->getStatus();
                break;
        }

        s($status);

        return $status;
    }

    protected function updateSourceEntity()
    {
        if ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId())
        {
            return;
        }

        $sourceStatus = $this->getSourceStatusFromReconEntityStatus();
        s($sourceStatus);

        $this->source->setStatus($sourceStatus);
        $this->source->setUtr($this->fta->getUtr());
        $this->source->setRemarks($this->fta->getRemarks());

        $this->repo->saveOrFail($this->source);
    }

    protected function isMerchantLevelError(): bool
    {
        return false;
    }
}