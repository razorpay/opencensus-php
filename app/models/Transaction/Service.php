<?php

namespace Models\Transaction;

use Models\Base;
use Models\Transaction;

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
}