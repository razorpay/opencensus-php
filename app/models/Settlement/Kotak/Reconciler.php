<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement\Status;

class Reconciler
{
    public function __construct()
    {
        $this->merchantRepo = new Merchant\Repository;
        $this->setlRepo = new \Models\Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
    }

    public function process($input)
    {
        $reconcileFile = $input['setlReconciliationFile'];

        $data = $this->parseReconciliationFile($reconcileFile);

        $this->reconcile($data);
    }

    protected function parseReconciliationFile($file)
    {
        $filePath = $file->getRealPath();

        $file = fopen($filePath, 'r');

        $txt = fread($file, filesize($filePath));

        $rows = explode('\n', $txt);

        $data = array();

        $headings = SettlementReconciliationGenerator::getHeadings();

        foreach ($rows as $row)
        {
            if ($row === '')
                continue;

            $values = explode('~', $row);
            unset($values[count($values) - 1]);

            $values = array_combine($headings, $values);

            $data[] = $values;
        }

        return $data;
    }

    protected function reconcile($data)
    {
        foreach ($data as $row)
        {
            $setl = $this->loadSettlementAndRelations($row);

            $this->processSettlementStatus($setl, $row);
        }
    }

    protected function processSettlementStatus($setl, $row)
    {
        $status = $row['Success'];

        $utr = $row['UTR'];
        $utr = ($utr === '') ? null : $utr;

        $setl->setUtr($utr);

        $failureReason = $row['Failure Reason'];

        if ($status === 'P')
        {
            $setl->setStatus(Status::TRANSFERRED);
        }
        else
        {
            $setl->setStatus(Status::FAILED);

            if ($failureReason !== '')
            {
                $setl->setAttribute(Settlement\Entity::FAILURE_REASON, $failureReason);
            }

            if (($status !== 'C') or
                ($failureReason === ''))
            {
                // Trace this
                // @todo: Raise this issue with Kotak bank to get the actual reason
            }

            // @todo: handle failure case
        }

        $this->setlRepo->save($setl);
    }

    protected function loadSettlementAndRelations($row)
    {
        $merchantId = $row['Payment Details 2'];
        $merchant = $this->merchantRepo->findOrFail($merchantId);

        $setlId = $row['Payment Details 1'];

        \Models\Settlement\Entity::verifyIdAndStripSign($setlId);

        $setl = $this->setlRepo->findOrFail($setlId);

        if ($merchantId !== $setl->getMerchantId())
        {
            throw new Exception\LogicException(
                'Merchant id must match. ' . $merchantId . ' ' . $setlId->getMerchantId());
        }

        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }
}