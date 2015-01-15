<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Excel;
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
        ;
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
}