<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_STATEMENT_DETAILS;

    public function fetchByAccountNumberAndChannel(string $accountNumber, string $channel)
    {
        $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $BASDetailsDbColumns = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($BASDetailsDbColumns)
                    ->where($accountNumberColumn, '=', $accountNumber)
                    ->where($channelColumn, '=', $channel)
                    ->first();
    }
}
