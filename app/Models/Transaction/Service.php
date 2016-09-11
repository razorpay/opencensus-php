<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
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
}
