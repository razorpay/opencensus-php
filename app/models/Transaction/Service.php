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

    public function getMonthlyReport($input)
    {
        $merchantId = $input['merchant_id'];
        $month = (int) $input['month'];
        $year = (int) $input['year'];

        $txns = (new Transaction\Repository)->fetchTransactionByMonthAndMerchantId(
                                                $merchantId, $month, $year);

        $reportTxns = array();

        foreach ($txns as $txn)
        {
            array_push($reportTxns, $this->toArrayReport($txn));
        }

        return $reportTxns;
    }

    protected function toArrayReport($txn)
    {
        $reportTxn = $txn->toArrayPublic();

        $reportTxn['created_at'] = date('d/m/y', $txn['created_at']);
        $reportTxn['debit'] = $txn['debit'] / 100;
        $reportTxn['credit'] = $txn['credit'] / 100;
        $reportTxn['fee'] = $txn['fee'] / 100;
        $reportTxn['service_tax'] = $txn['service_tax'] / 100;

        $reportTxn['settled_at'] = null;

        if ($txn['settled_at'] !== null)
        {
            $reportTxn['settled_at'] = date('d/m/y', $txn['settled_at']);
        }

        if ($txn->isTypePayment())
        {
            $reportTxn['description'] = $txn->entity->getDescription();
            $reportTxn['notes'] = $txn->entity->getNotesJson();
        }

        return $reportTxn;
    }
}