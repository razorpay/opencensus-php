<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Transaction;

class Service extends Base\Service
{
    public function getTransactionRecords($input)
    {
        $txns = (new Transaction\Repository)->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function getTransactionRecordById($id)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        $txn = (new Transaction\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $txn->toArrayPublic();
    }

    public function settlementFixer()
    {
        $repo = new Transaction\Repository;

        return $repo->transaction(function()
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
