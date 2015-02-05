<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement\Kotak;
use Trace;
use Trace\TraceCode;
/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class ReconciliationGenerator
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    protected static $extraHeadings = array(
        'Success',
        'UTR',
        'Failure Reason',
        'Date');

    public function _construct()
    {
        $this->mode = \App::getFacadeRoot()['rzp.mode'];

        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }

    public function generateReconcileFile($input)
    {
//        $setlFile = $input['setlFile'];
        $setlFile = $this->getFileIfExists();

        if ($setlFile === null)
            return [];


        $data = $this->parseTextFile($setlFile);

        $data = $this->addNewFields($data);

        $txt = $this->generateText($data);

        $file = $this->writeToTextFile($txt);

        Trace::info(TraceCode::SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED);

        return $file;
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