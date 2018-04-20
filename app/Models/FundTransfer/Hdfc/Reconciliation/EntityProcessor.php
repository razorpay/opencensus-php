<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function isMerchantLevelError(): bool
    {
        return false;
    }
}
