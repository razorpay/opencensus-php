<?php

namespace Models\Transaction;

use Carbon\Carbon;
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
        ini_set('memory_limit', '1024M');

        $merchantId = $input['merchant_id'];
        $month = (int) $input['month'];
        $year = (int) $input['year'];

        $txns = (new Transaction\Repository)->fetchTransactionByMonthAndMerchantId(
                                                $merchantId, $month, $year);

        $reportTxns = array();

        date_default_timezone_set('Asia/Kolkata');

        foreach ($txns as $txn)
        {
            $reportTxn = $this->toArrayReport($txn);

            if ($reportTxn !== null)
            {
                array_push($reportTxns, $this->toArrayReport($txn));
            }
        }

        return $reportTxns;
    }

    protected function toArrayReport($txn)
    {
        $reportTxn = $txn->toArrayPublic();

        unset($reportTxn['id']);
        unset($reportTxn['entity']);

        $reportTxn['created_at'] = date('d/m/y', $txn['created_at']);
        $reportTxn['amount'] = $txn['amount'] / 100;
        $reportTxn['debit'] = $txn['debit'] / 100;
        $reportTxn['credit'] = $txn['credit'] / 100;
        $reportTxn['fee'] = $txn['fee'] / 100;
        $reportTxn['service_tax'] = $txn['service_tax'] / 100;

        $reportTxn['settled_at'] = null;
        $reportTxn['description'] = null;
        $reportTxn['notes'] = null;
        $reportTxn['payment_id'] = null;

        if ($txn['settled_at'] !== null)
        {
            $reportTxn['settled_at'] = date('d/m/y', $txn['settled_at']);
            $reportTxn['settlement_id'] = $reportTxn['settlement_id'];
        }

        if ($txn->isTypePayment())
        {
            $payment = $txn->entity;

            $reportTxn['description'] = $payment->getDescription();
            $reportTxn['notes'] = $payment->getNotesJson();

            if ($payment->hasBeenCaptured() === false)
            {
                return;
            }
        }
        else if ($txn->isTypeRefund())
        {
            $refund = $txn->entity;
            $payment = $refund->payment;

            if ($payment->hasBeenCaptured() === false)
            {
                return;
            }

            $reportTxn['payment_id'] = $payment->getPublicId();
        }

        return $reportTxn;
    }
}