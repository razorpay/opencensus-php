<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Models\Transaction\ReconciledType;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
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
