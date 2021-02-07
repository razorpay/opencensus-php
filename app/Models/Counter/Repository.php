<?php

namespace RZP\Models\Counter;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'counter';

    public function getCounterByAccountTypeAndBalanceId($accountType,
                                                        $balanceId)
    {
        $balanceIdColumn = $this->repo->counter->dbColumn(Entity::BALANCE_ID);

        $accountTypeColumn = $this->repo->counter->dbColumn(Entity::ACCOUNT_TYPE);

        return $this->newQuery()
                    ->where($accountTypeColumn, $accountType)
                    ->where($balanceIdColumn, $balanceId)
                    ->first();
    }
}
