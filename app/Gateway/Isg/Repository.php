<?php

namespace RZP\Gateway\Isg;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'isg';

    public function fetchByBankReferenceNumber($transactionId)
    {
        return $this->newQuery()
            ->where('bank_reference_no',  '=', $transactionId)
            ->firstOrFail();
    }
}
