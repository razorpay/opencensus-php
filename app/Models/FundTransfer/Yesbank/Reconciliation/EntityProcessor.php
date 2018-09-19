<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

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
}
