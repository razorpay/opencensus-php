<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class ReturnTransactionsGenerator
{
    use Kotak\FileHandlerTrait;

    protected static $filename = 'Kotak_Return_Transaction';

    protected static $accountIfsc = 'KKBK0000958';
    protected static $accountType = '11';
    protected static $accountNumber = '12345';
    protected static $accountName = 'abcd';

    public function generate($input)
    {
        $data = array();

        $batchtime = ((string) random_integer(2)) . '00';

        $setlRepo = new Settlement\Repository;

        foreach ($input as $row)
        {
            $setlId = $row['id'];

            Settlement\Entity::verifyIdAndStripSign($setlId);
            $setl = $setlRepo->findOrFail($setlId);

            $referUtr = (bool) $row['refer_utr'];

            $data[] = array(
                'BATCHTIME'         => $batchtime ,
                'TXN REF NO'        => $setl->getPublicId(),
                'SND BRN IFSC'      => 'xyz',
                'ACCT TYP1'         => '11',
                'SEND CUST ACNO'    => '12345',
                'SEND CUST ACNAME'  => 'OOOO',
                'BENF IFSC'         => 'KKBK0000958',
                'BENE CUST ACTYP'   => '11',
                'BENE CUST ACNO'    => '11111',
                'BENE CUST ACNAME'  => 'ABC',
                'RETURN UTR NO1'    => '',
                'REMITT INFO'       => 'some random info',
                'AMOUNT'            => '',
                'RETURN REASON'     => '',
                'APAC'              => '111111',
                'TXN DATE'          => '11/01/2014 08:01:13',
                'VIRTUAL APC'       => '');
        }

        $txt = $this->generateText($data);

        $filename = $this->writeToTextFile($txt);

        return $filename;
    }

    public static function getHeadings()
    {
        return ReturnTransactions::getHeadings();
    }
}