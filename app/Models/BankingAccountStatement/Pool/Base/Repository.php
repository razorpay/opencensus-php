<?php

namespace RZP\Models\BankingAccountStatement\Pool\Base;

use Carbon\Carbon;
use Database\Connection;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Settlement\SlackNotification;

class Repository extends Base\Repository
{
    protected $connection = Connection::RX_ACCOUNT_STATEMENTS_LIVE;

    public function newQuery()
    {
        return $this->newQueryWithConnection($this->getRxStatementConnection());
    }

    public function bulkInsert($records)
    {
        $this->newQuery()
             ->insert($records);

        // This is another approach to do inserts. Keeping it here for future reference if required.
        //Entity::insert($records);
    }

    public function findLatestByAccountNumber($accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->latest(Entity::ID)
                    ->first();
    }

    public function findExistingStatementRecordsForBankWithDate(array $bankTransactionIds, $accountNumber, $queryDate)
    {
        $columns = [];

        $columns[] = $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $columns[] = $bankTransactionIdColumn = $this->dbColumn(Entity::BANK_TRANSACTION_ID);

        $columns[] = $this->dbColumn(Entity::AMOUNT);

        $columns[] = $this->dbColumn(Entity::CURRENCY);

        $columns[] = $this->dbColumn(Entity::TYPE);

        $columns[] = $this->dbColumn(Entity::DESCRIPTION);

        $columns[] = $this->dbColumn(Entity::CATEGORY);

        $columns[] = $this->dbColumn(Entity::BANK_SERIAL_NUMBER);

        $columns[] = $this->dbColumn(Entity::BANK_INSTRUMENT_ID);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $columns[] = $this->dbColumn(Entity::BALANCE_CURRENCY);

        $columns[] = $this->dbColumn(Entity::POSTED_DATE);

        $columns[] = $this->dbColumn(Entity::TRANSACTION_DATE);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $createdAtColumn = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
                    ->whereIn($bankTransactionIdColumn, $bankTransactionIds)
                    ->where($createdAtColumn, '>=', $queryDate)
                    ->where($accountNumberColumn, $accountNumber)
                    ->useWritePdo()
                    ->get($columns);
    }

    public function findExistingStatementRecordsForBank(array $records)
    {
        $columns = [];

        $columns[]  = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $columns[] = $this->dbColumn(Entity::BANK_TRANSACTION_ID);

        $columns[] = $this->dbColumn(Entity::AMOUNT);

        $columns[] = $this->dbColumn(Entity::CURRENCY);

        $columns[] = $this->dbColumn(Entity::TYPE);

        $columns[] = $this->dbColumn(Entity::DESCRIPTION);

        $columns[] = $this->dbColumn(Entity::CATEGORY);

        $columns[] = $this->dbColumn(Entity::BANK_SERIAL_NUMBER);

        $columns[] = $this->dbColumn(Entity::BANK_INSTRUMENT_ID);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $columns[] = $this->dbColumn(Entity::BALANCE_CURRENCY);

        $columns[] = $this->dbColumn(Entity::POSTED_DATE);

        $columns[] = $this->dbColumn(Entity::TRANSACTION_DATE);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $uniqueColumns = [];

        $uniqueColumns[] = $bankTransactionIdColumn = $this->dbColumn(Entity::BANK_TRANSACTION_ID);

        $uniqueColumns[] = $bankSerialNumberColumn = $this->dbColumn(Entity::BANK_SERIAL_NUMBER);

        $uniqueColumns[] = $bankTransactionDateColumn = $this->dbColumn(Entity::TRANSACTION_DATE);

        $uniqueColumns[] = $bankTransactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $uniqueColumns[] = $bankTransactionChannelColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        return $this->newQuery()
                    ->whereInMultiple($uniqueColumns, $records)
                    ->get($columns);
    }
}
