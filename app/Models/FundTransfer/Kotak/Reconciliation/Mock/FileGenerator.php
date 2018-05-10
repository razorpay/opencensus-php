<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Mock;

use App;
use Carbon\Carbon;
use Excel;

use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\NodalAccount;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Reconciliation\Mock\Generator;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class FileGenerator extends Generator
{
    use FileHandlerTrait;

    const CHANNEL   = Settlement\Channel::KOTAK;

    protected static $fileToReadName  = 'Kotak_Settlement';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    public function __construct()
    {
        parent::__construct();
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
            return [];

        $this->initRequestParams($input);

        $prevAttemptId = null;
        if (empty($input['prev_attempt_id']) === false)
        {
            $prevAttemptId = $input['prev_attempt_id'];
        }

        $data = $this->parseTextFile($setlFile);

        // Modify data to replicate Kotak bug
        // As per the bug, Kotak does the following on reading settlement file
        $modifiedData = [];

        foreach ($data as $row)
        {
            $row[Headings::PAYMENT_DETAILS_4] = $row[Headings::PAYMENT_DETAILS_3];
            $row[Headings::PAYMENT_DETAILS_3] = $row[Headings::PAYMENT_DETAILS_2];
            $row[Headings::PAYMENT_DETAILS_2] = $row[Headings::PAYMENT_DETAILS_1];
            $row[Headings::PAYMENT_DETAILS_1] = '';

            $modifiedData[] = $row;
        }

        // $prevAttemptId is the ID of the previous fund_transfer_attempt
        //
        // This value needs to be passed when we need the reconciliation file to
        // contain the recon data for that attempt also, besided for the latest attempt
        //
        // It will be a failed row because more than 1 attempt for a settlement signifies
        // that the previous attempts had failed
        //
        // Note: This works only when there's only settlement ID for which the reconciliation
        // is to be generated
        $failedRow = null;
        if ($prevAttemptId !== null)
        {
            // Copt the success row
            $failedRow = $modifiedData[0];

            // Change the attempt ID in that row
            $failedRow[Headings::PAYMENT_REF_NO] = $prevAttemptId;

            $newFields = $this->generateReconciliationFields(Carbon::now(Timezone::IST));

            $failedRow = array_merge($failedRow, $newFields);
        }

        $modifiedData = $this->addNewFields($modifiedData);

        if ($failedRow !== null)
        {
            $modifiedData[] = $failedRow;
        }

        $txt = $this->generateText($modifiedData);

        $filename = $this->getFileToWriteName();

        $file = $this->createTxtFile($filename, $txt);

        $this->trace->info(TraceCode::SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED);

        return $file;
    }

    public static function getHeadings()
    {
        return NodalAccount::getHeadings();
    }

    protected function addNewFields(array $data)
    {
        $date = Carbon::now(Timezone::IST);

        foreach ($data as &$row)
        {
            $newFields = $this->generateReconciliationFields($date);

            $row = array_merge($row, $newFields);
        }

        return $data;
    }

    protected function generateReconciliationFields(Carbon $datetime)
    {
        $utr = random_integer(10);

        $data = [
            Headings::STATUS_OF_TRANSACTION     => Status::PROCESSED,
            Headings::UTR_NUMBER                => 'KKBKH1' . $utr,
            Headings::REMARKS                   => '',
            Headings::DATE_TIME                 => $datetime->format('d/m/Y H:i:s'),
            Headings::PAYMENT_DATE              => $datetime->format('d-M-y'),
            Headings::INSTRUMENT_DATE           => $datetime->format('d-M-y'),
            Headings::CMS_REF_NO                => 'kotak',
            Headings::DUMMY                     => ''
        ];

        if ($this->generateFailedReconciliations === true)
        {
            $data[Headings::REMARKS]                = 'Some failure.';
        }

        return $data;
    }
}
