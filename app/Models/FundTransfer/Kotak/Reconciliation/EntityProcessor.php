<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected function getAttemptStatus(): string
    {
        $status = Attempt\Status::FAILED;

        $bankStatusCode = $this->fta->getBankStatusCode();

        if ($bankStatusCode === Status::PROCESSED)
        {
            $remarks = $this->fta->getRemarks();

            if ((empty($remarks) === false) and
                (in_array($remarks, self::SUCCESS_STATUS) === false))
            {
                $status = Attempt\Status::FAILED;
            }
            else
            {
                $status = Attempt\Status::PROCESSED;
            }
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

        if ($this->source->getEntity() !== Attempt\Type::REFUND)
        {
            $this->source->setFailureReason($this->fta->getFailureReason());
        }

        $this->source->saveOrFail();
    }

    protected function isMerchantLevelError(): bool
    {
        $ftaStatus = $this->fta->getStatus();

        $utr = $this->fta->getUtr();

        $bankStatusCode = $this->fta->getBankStatusCode();

        if (($ftaStatus === Attempt\Status::FAILED) and
            (empty($utr) === false) and
            ($bankStatusCode === Status::PROCESSED))
        {
            return true;
        }

        return false;
    }
}