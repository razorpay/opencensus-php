<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER;

    public function findByTransactionId($transactionId)
    {
        return $this->newQuery()
                    ->where(Entity::TRANSACTION_ID, '=', $transactionId)
                    ->first();
    }
}
