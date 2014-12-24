<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Models\Merchant;
use Models\Transaction;

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
        $this->validateInput($input);

        $data = $this->getData($input);

        $this->reconcile($data);
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
        $status = $row[19];

        $utr = $row['utr'];
        $utr = ($utr === '') ? null : $utr;

        $setl->setUtr($utr);

        $failureReason = $row['failure'];

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
    }

    protected function loadSettlementAndRelations($row)
    {
        $merchantId = $row['Payment Details 3'];
        $merchant = $this->merchantRepo->findOrFail($merchantId);

        $setlId = $row['Payment Details 1'];
        Settlement\Entity::verifyIdAndStripSign($setlId);

        if ($merchantId !== $setlId->getMerchantId())
        {
            throw new Exception\LogicException(
                'Merchant id must match. ' . $merchantId . ' ' . $setlId->getMerchantId());
        }

        $setl = $this->setlRepo->findOrFail($setlId);
        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }

    protected function validateInput($input)
    {
        ;
    }

    protected getData($input)
    {
        $raw = $this->getRawDataFromFile($input['file']);

        $data = $this->extractTabularData($raw);

        return $data;
    }

    protected function getRawDataFromFile($mprFile)
    {
        $filePath = $mprFile->getRealPath();

        $raw = Excel::load($filePath)
                      ->noHeading()
                      ->ignoreEmpty()
                      ->formatDates(false)
                      ->toArray();

        return $raw;
    }

    protected function extractTabularData($raw)
    {
        $headings = Settlement::$headings;
        $headings[] = 'Symbol';

        $data = array();

        foreach ($raw as &$row)
        {
            $values = explode('~', $row);
            $data[] = array_combine($headings, $values);
        }

        return $data;
    }
}