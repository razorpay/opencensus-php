<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{

    /**
     * Remark messages received when the transaction failed from our end.
     * Remark will start with the below string in such case
     */
    const INTERNAL_FAILURE_REMARK = 'Rejected by RTGS Gateway';

    protected function isMerchantLevelError(): bool
    {
        $remarks = $this->fta->getRemarks();

        $status  = $this->fta->getStatus();

        if (($status === Attempt\Status::FAILED) and
            (empty($remarks) === false))
        {
            return (stripos($remarks, self::INTERNAL_FAILURE_REMARK) === false);
        }

        return false;
    }
}
