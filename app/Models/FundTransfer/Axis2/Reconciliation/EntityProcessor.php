<?php

namespace RZP\Models\FundTransfer\Axis2\Reconciliation;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
        $remarks = $this->fta->getRemarks();

        $status  = $this->fta->getStatus();

        if (($status === Attempt\Status::FAILED) and
            (empty($remarks) === false))
        {
            $flag = Status::isCriticalError($this->fta);

            return $flag;
        }

        return false;
    }
}
