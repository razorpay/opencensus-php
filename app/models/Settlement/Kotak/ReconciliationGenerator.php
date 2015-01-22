<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement\Kotak;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class ReconciliationGenerator
{
    use FileHandlerTrait;

    protected static $filename = 'Kotak_Settlement_Reconciliation';

    protected static $extraHeadings = array(
        'Success',
        'UTR',
        'Failure Reason',
        'Date');

    public function _construct()
    {
        ;
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $input['setlFile'];

        $data = $this->parseTextFile($setlFile);

        $data = $this->addNewFields($data);

        $txt = $this->generateText($data);

        return $this->writeToTextFile($txt);
    }

    public static function getHeadings()
    {
        return Kotak\NodalAccount::getHeadings();
    }

    protected function addNewFields($data)
    {
        $date = Carbon::today('Asia/Kolkata')->format('d/m/Y H:i:s');

        foreach ($data as &$row)
        {
            $utr = random_integer(10);
            $newFields = array(
                'Success'           => 'P',
                'UTR'               => 'KKBKH1' . $utr,
                'Failure Reason'    => '',
                'Date'              => $date);

            $row = array_merge($row, $newFields);
        }

        return $data;
    }
}