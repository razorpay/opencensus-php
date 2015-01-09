<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Models\Merchant;
use Models\Transaction;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class SettlementReconciliationGenerator
{
    protected $headings;

    protected $extraHeadings = array(
        'Success',
        'UTR',
        'Failure Reason',
        'Date');

    public function _construct()
    {
        $this->headings = Settlement::$headings;
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $input['setlFile'];

        $data = $this->parseSettlementFile($setlFile);

        $data = $this->addNewFields($data);

        $txt = $this->generateSetlReconciliationText($data);

        return $this->generateSetlReconciliationFile($txt);
    }

    protected function addNewFields($data)
    {
        $date = Carbon::today('Asia/Kolkata')->format('d/m/Y H:i:s');

        foreach ($data as $row)
        {
            $utr = random_integer(10);
            $newFields = array(
                'Success'           => 'C',
                'UTR'               => 'KKBKH1' . $utr,
                'Failure Reason'    => '',
                'Date'              => $date);
        }

        return $data;
    }

    protected function parseSettlementFile($file)
    {
        $filePath = $file->getRealPath();

        $data = Excel::load($filePath)
                      ->formatDates(false)
                      ->toArray();

        //
        // Excel files can have multiple sheets.
        // We only need to get data from first sheet.
        //
        return $data;
    }

    protected function generateSetlReconciliationText($data)
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= implode('~', array_values($row)) . '~\n';
        }

        return $txt;
    }

    protected function generateSetlReconciliationFile($txt)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y_H:i:s');
        $name = 'Kotak_Settlement_Reconciliation_File_'.$time.'.txt';
        $path = storage_path() . '/files/settlement/';

        $fullpath = $path . $name;

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);

        return $fullpath;
    }
}