<?php

namespace RZP\Models\Settlement\Kotak;

use App;
use Carbon\Carbon;
use Excel;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

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
        'Status Of transaction',
        'UTR number',
        'Reject Reason',
        'DateTime',
        'Int.ref no.',
        'Dummy',
    );

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
            return [];

        $data = $this->parseTextFile($setlFile);

        $data = $this->addNewFields($data);

        $txt = $this->generateText($data);

        $file = $this->writeToTextFile($txt);

        $this->trace->info(TraceCode::SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED);

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
                'Status Of transaction' => 'P',
                'UTR number'            => 'KKBKH1' . $utr,
                'Reject Reason'         => '',
                'DateTime'              => $date,
                'Int.ref no.'           => 'kotak',
                'Dummy'                 => ''
            );

            $date = Carbon::createFromFormat('d/m/Y', $row['Payment_Date']);

            $row['Payment_Date'] = $date->format('d-M-y');

            $row = array_merge($row, $newFields);
        }

        return $data;
    }
}
