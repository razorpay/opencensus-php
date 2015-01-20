<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;

class Reconciler
{
    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    public function __construct()
    {
        $this->reconciledAt = time();

        $this->merchantRepo = new Merchant\Repository;
        $this->setlRepo = new \Models\Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
    }

    public function process($input)
    {
        $reconcileFile = $input['setlReconciliationFile'];

        $data = $this->parseReconciliationFile($reconcileFile);

        return $this->reconcile($data);
    }

    protected function parseReconciliationFile($file)
    {
        $filePath = $file->getRealPath();

        $file = fopen($filePath, 'r');

        $txt = fread($file, filesize($filePath));

        $rows = explode('\n', $txt);

        $data = array();

        $headings = Kotak\ReconciliationGenerator::getHeadings();

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
        $collection = new Base\PublicCollection;

        foreach ($data as $row)
        {
            $setl = $this->loadSettlementAndRelations($row);

            $setl = $this->processSettlementStatus($setl, $row);

            $collection->push($setl);
        }

        return $collection;
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
            $setl->setStatus(Settlement\Status::TRANSFERRED);
            $this->setlRepo->save($setl);
        }
        else
        {
            if ($failureReason !== '')
            {
                $failureReason = 'Reconciliation: ' . $failureReason;
            }

            (new Failure)->markFailed($setl, $reason);

            if (($status !== 'C') or
                ($failureReason === ''))
            {
                // Trace this
                // @todo: Raise this issue with Kotak bank to get the actual reason
            }
        }

        $setl->transaction->setReconciledAt($this->reconciledAt);
        $this->txnRepo->save($setl->transaction);

        return $setl;
    }

    protected function loadSettlementAndRelations($row)
    {
        $setlId = $row['Payment_Ref_No.'];
        Settlement\Entity::verifyIdAndStripSign($setlId);
        $setl = $this->setlRepo->findOrFail($setlId);

        $merchantId = $row['Payment Details 1'];
        $merchant = $this->merchantRepo->findOrFail($merchantId);

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