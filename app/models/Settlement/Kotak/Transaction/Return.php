<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Excel;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;

class ReturnTransactions
{
    protected static $headings = array(
        'BATCHTIME',
        'TXN REF NO',
        'SND BRN IFSC',
        'ACCT TYP1',
        'SEND CUST ACNO',
        'SEND CUST ACNAME',
        'BENF IFSC',
        'BENE CUST ACTYP',
        'BENE CUST ACNO',
        'BENE CUST ACNAME',
        'RETURN UTR NO1',
        'REMITT INFO',
        'AMOUNT',
        'RETURN REASON',
        'APAC',
        'TXN DATE',
        'VIRTUAL APC');

    public function __construct()
    {
        $this->merchantRepo = new Merchant\Repository;
        $this->setlRepo = new \Models\Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
    }

    public function process($input)
    {
        $returnFile = $input['setlReturnFile'];

        $data = $this->parseReturnFile($returnFile);

        $this->reconcileReturns($data);
    }

    protected function reconcileReturns($data)
    {
        $collection = new Base\PublicCollection;

        foreach ($data as $row)
        {
            $setl = $this->loadSettlementAndRelations($row);

            $setl = $this->processSettlementFailure($setl, $row);

            $collection->push($setl);
        }

        return $collection;
    }

    protected function processSettlementFailure($setl, $row)
    {
        $setl->setStatus(Settlement\Status::FAILED);

        $failureReason = null;

        if ($row['RETURN UTR NO1'] !== null)
        {
            $returnUtr = $row['RETURN UTR NO1'];
            $setl->setAttribute(Settlement\Entity::RETURN_UTR, $returnUtr);

            $failureReason = 'Return reason: ' . $row['RETURN REASON'];
        }
        else
        {
            $failureReason = 'Remitt info: ' . $row['REMITT INFO'];
        }

        $setl->setAttribute(Settlement\Entity::FAILURE_REASON, $failureReason);
    }

    protected function parseReturnFile($file)
    {
        $filePath = $file->getRealPath();

        $rows = Excel::load($filePath, function($reader)
                        { $reader->noHeading(); })
                      ->formatDates(false)
                      ->toArray();

        $count = count($rows);

        $i = 2;

        $data = [];
        $headings = $rows[1];

        while ($i < $count)
        {
            $data[] = array_combine($headings, $rows[$i]);
            $i++;
        }

        return $data;
    }

    public static function getHeadings()
    {
        return static::$headings;
    }

    protected function loadSettlementAndRelations($row)
    {
        $setlId = $row['TXN REF NO'];
        Settlement\Entity::verifyIdAndStripSign($setlId);
        $setl = $this->setlRepo->findOrFail($setlId);

        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());
        $merchant = $this->merchantRepo->findOrFail($setl->getMerchantId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }
}