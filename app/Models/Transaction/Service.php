<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;
use RZP\Models\Transaction;

class Service extends Base\Service
{
    public function getTransactionRecords($input)
    {
        $txns = $this->repo->transaction->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function getTransactionRecordById($id)
    {
        return $this->repo->transaction->fetchAndReturnPublicArray($id, $this->merchant);
    }

    public function settlementFixer()
    {
        return $this->repo->transaction(function()
        {
            return (new BugFixer)->settlementFixerInTxn();
        });
    }

    public function getReport($input)
    {
        $report = new Base\Report;

        return $report->getReport($input, 'transaction');
    }

    public function migrateOlderTransactions()
    {
        return (new Transaction\DataMigration())->migrateOlderTransactions();
    }

    public function settleOlderTransactions($input)
    {
        return (new Transaction\DataMigration())->settleOlderTransactions($input);
    }

    public function addPricingRuleForeOlderTransactions()
    {
        return (new Transaction\DataMigration())->addPricingRuleForeOlderTransactions();
    }

}
