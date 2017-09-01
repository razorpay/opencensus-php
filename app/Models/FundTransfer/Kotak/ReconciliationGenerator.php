<?php

namespace RZP\Models\FundTransfer\Kotak;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Excel;
use RZP\Exception;
use RZP\Models\FileStore\Accessor;
use RZP\Models\FundTransfer;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use Illuminate\Http\UploadedFile;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class ReconciliationGenerator
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }

    public function reconcileSettlementsInTestMode(array $input)
    {
        list($startTimestamp, $endTimestamp) = $this->getTimestamps($input);

        $nonReconciledAttempts = $this->repo
                                      ->fund_transfer_attempt
                                      ->getAttemptsBetweenTimestampsWithStatus(
                                            $startTimestamp,
                                            $endTimestamp,
                                            FundTransfer\Attempt\Status::PENDING_RECONCILIATION);

        // get batch id of all above attempts
        $batchIds = $nonReconciledAttempts->pluck(FundTransfer\Attempt\Entity::BATCH_FUND_TRANSFER_ID)
                                          ->toArray();
        // non-reconciled batches
        $nonReconciledBatches = $this->repo->batch_fund_transfer->findManyByPublicIds($batchIds);

        // for above batch ids, get the txt file ids
        $setlFileIds = $nonReconciledBatches->pluck(FundTransfer\Batch\Entity::TXT_FILE_ID)
                                            ->toArray();
        // read one txt file from s3 at a time and generate recon file
        $response = [];

        foreach ($setlFileIds as $fileId)
        {
            $fileAccessor = (new Accessor)->id($fileId);

            $filePath = $fileAccessor->getFile();

            $file = new UploadedFile($filePath, basename($filePath));

            $reconFile = $this->generateReconcileFile(['file' => $file]);

            $file = new UploadedFile($reconFile, basename($reconFile));

            $data = (new Settlement\Service)->reconcileH2HSettlements(['file' => $file]);

            $response[] = $data;
        }

        return $response;
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
            return [];

        $generateFailedReconciliations = false;

        if(isset($input['failed_recons']) === true)
        {
            $generateFailedReconciliations = ($input['failed_recons'] === '1');
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

        $modifiedData = $this->addNewFields($modifiedData, $generateFailedReconciliations);

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

    protected function addNewFields(array $data, bool $generateFailedReconciliations = false)
    {
        $date = Carbon::now(Timezone::IST);

        foreach ($data as &$row)
        {
            $newFields = $this->generateReconciliationFields($date, $generateFailedReconciliations);

            $row = array_merge($row, $newFields);
        }

        return $data;
    }

    protected function generateReconciliationFields(Carbon $datetime, bool $generateFailedReconciliations)
    {
        $utr = random_integer(10);

        $data = [
            Headings::STATUS_OF_TRANSACTION     => 'P',
            Headings::UTR_NUMBER                => 'KKBKH1' . $utr,
            Headings::REMARKS                   => '',
            Headings::DATE_TIME                 => $datetime->format('d/m/Y H:i:s'),
            Headings::PAYMENT_DATE              => $datetime->format('d-M-y'),
            Headings::INSTRUMENT_DATE           => $datetime->format('d-M-y'),
            Headings::CMS_REF_NO                => 'kotak',
            Headings::DUMMY                     => ''
        ];

        if ($generateFailedReconciliations === true)
        {
            $data[Headings::REMARKS]  = 'This is a string which test characters count limit.' .
                ' This is a string which test characters count limit. This is a string which' .
                ' test characters count limit. This is a string which test characters count limit.' .
                ' This is a string which test characters count limit.';
        }

        return $data;
    }

    protected function getTimestamps(array $input): array
    {
        if (isset($input['on']) === true)
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->setTime(0,0,0);

            $startTimestamp = $from->getTimestamp();

            $endTimestamp = $from->addDay()->getTimestamp() - 1;
        }
        else
        {
            $startTimestamp = Carbon::today("Asia/Kolkata")->getTimestamp();

            $endTimestamp = Carbon::tomorrow("Asia/Kolkata")->getTimestamp() - 1;
        }

        return [$startTimestamp, $endTimestamp];
    }
}
