<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
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

        $data = $this->parseReconciliationFile($reconcileFile);

        $this->reconcile($data);
    }

    public static function getHeadings()
    {
        return static::$headings;
    }
}