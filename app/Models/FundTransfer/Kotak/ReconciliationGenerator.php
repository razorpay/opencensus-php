<?php

namespace RZP\Models\FundTransfer\Kotak;

use App;
use Carbon\Carbon;
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

    public function reconcileSettlementsInTestMode($input)
    {
        $startTimestamp = Carbon::today("Asia/Kolkata")->timestamp;

        $endTimestamp = Carbon::tomorrow("Asia/Kolkata")->timestamp - 1;

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

        $generateFailedReconciliations = false;

        if(isset($input['failed_recons']) === true)
        {
            $generateFailedReconciliations = ($input['failed_recons'] === '1');
        }

        if ($setlFile === null)
            return [];

        $data = $this->parseTextFile($setlFile);

        $data = $this->addNewFields($data, $generateFailedReconciliations);

        $txt = $this->generateText($data);

        $file = $this->writeToTextFile($txt);

        $this->trace->info(TraceCode::SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED);

        return $file;
    }

    public static function getHeadings()
    {
        return NodalAccount::getHeadings();
    }

    protected function addNewFields($data, $generateFailedReconciliations = false)
    {
        $date = Carbon::today('Asia/Kolkata')->format('d/m/Y H:i:s');

        foreach ($data as &$row)
        {
            $newFields = $this->generateReconciliationFields($date, $generateFailedReconciliations);

            $date = Carbon::createFromFormat('d/m/Y', $row['Payment_Date']);

            $row[Headings::PAYMENT_DATE] = $date->format('d-M-y');

            $row = array_merge($row, $newFields);
        }

        return $data;
    }

    protected function generateReconciliationFields($date, $generateFailedReconciliations)
    {
        $utr = random_integer(10);

        $data = [
            Headings::STATUS_OF_TRANSACTION     => 'P',
            Headings::UTR_NUMBER                => 'KKBKH1' . $utr,
            Headings::REMARKS                   => '',
            Headings::DATE_TIME                 => $date,
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
}
