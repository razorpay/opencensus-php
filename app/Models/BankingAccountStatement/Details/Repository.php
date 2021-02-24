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

    public function fetchAccountNumbersByChannelOrderByLastStatementAttemptAt(string $channel)
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->where($statusColumn, '=', Status::ACTIVE)
                    ->oldest(Entity::LAST_STATEMENT_ATTEMPT_AT)
                    ->get();
    }
}
