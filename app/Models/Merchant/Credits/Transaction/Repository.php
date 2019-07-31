<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'credit_transaction';

    /**
     * returns all credits logs of a transaction sorted in descending order of id
     *
     * @param string $transactionId
     * @return mixed
     */

    public function getAllCreditLogsOfTransaction(string $transactionId)
    {
        return $this->newQuery()
                    ->where(Entity::TRANSACTION_ID, '=', $transactionId)
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }
}
