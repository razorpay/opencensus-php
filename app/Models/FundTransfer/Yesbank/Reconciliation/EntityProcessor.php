<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Models\Transaction\ReconciledType;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
        $bankStatusCode = $this->fta->getBankStatusCode();

        $merchantFailures = Status::getMerchantFailures();

        if (in_array($bankStatusCode, $merchantFailures, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function updateTransactionEntity($reconciledType = ReconciledType::MIS)
    {
        $this->source->transaction->setReconciledAt($this->reconciledAt);

        //
        // For yesbank reconciliation is API based
        //
        $this->source->transaction->setReconciledType(ReconciledType::NA);

        $this->source->transaction->saveOrFail();
    }
}
