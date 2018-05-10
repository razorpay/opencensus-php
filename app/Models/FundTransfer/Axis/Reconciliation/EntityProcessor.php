<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
        $status         = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        if (($status === Attempt\Status::FAILED) and
            ($bankStatusCode === Status::RETURNSETTLED))
        {
            return true;
        }

        return false;
    }
}
