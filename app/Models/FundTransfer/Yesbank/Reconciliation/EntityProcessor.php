<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Constants\Entity;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
        $bankStatusCode = $this->fta->getBankStatusCode();

        $bankResponseCode = $this->fta->getBankResponseCode();

        if ($this->fta->shouldUseGateway($this->fta->getMode()) === true)
        {
            $merchantFailures = GatewayStatus::getMerchantFailures();

            $isMerchantError = GatewayStatus::inStatus($merchantFailures, $bankStatusCode, $bankResponseCode);
        }
        else
        {
            $merchantFailures = Status::getMerchantFailures();

            $isMerchantError = Status::inStatus($merchantFailures, $bankStatusCode, $bankResponseCode);
        }

        if ($isMerchantError === true)
        {
            return true;
        }

        return false;
    }

    protected function updateTransactionEntity($reconciledType = ReconciledType::MIS)
    {
        // Source entity might update the transaction but because we would have already fetched
        // the transaction from source earlier. Then if we try to access $this->source->transaction now,
        // It will return an old copy. Not the updated transaction. Hence, we reload the relation.
        $this->source->load(Entity::TRANSACTION);

        $this->source->transaction->setReconciledAt($this->reconciledAt);

        //
        // For yesbank reconciliation is API based
        //
        $this->source->transaction->setReconciledType(ReconciledType::NA);

        $this->source->transaction->saveOrFail();
    }
}
