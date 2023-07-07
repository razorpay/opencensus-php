<?php

namespace RZP\Reconciliator\Base;

use App;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use Razorpay\Trace\Logger;
use RZP\Reconciliator\Service;
use RZP\Reconciliator\Messenger;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\Batch\Processor\Reconciliation;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Reconciliator\Base\Foundation\SubReconciliate;
use RZP\Reconciliator\Base\SubReconciliator\PaymentReconciliate;

class Reconciliate extends Base\Core
{
    use FileHandlerTrait;

    /***********************
     * Reconciliation Types
     ***********************/

    const NODAL          = 'nodal';
    const PAYMENT        = 'payment';
    const REFUND         = 'refund';
    const COMBINED       = 'combined';
    const MANUAL         = 'manual';
    const EMANDATE_DEBIT = 'emandate_debit';

    // Before sending requests to scrooge api, we
    // need to break them into chunks of this size
    const SCROOGE_CHUNK_SIZE = 1000;

    public static $forceAuthorizedPayments = [];

    /**
     * This is being used as a hack to ignore the unexpected files coming
     * from NB-ICICI. We return recon type as 'invalid_recon_type'
     * instead of null AND use this to decide whether to throw slack
     * alert when recon type does not belong to VALID_RECON_TYPES.
     */
    const INVALID_RECON_TYPE = 'invalid_recon_type';

    const VALID_RECON_TYPES = [self::NODAL, self::PAYMENT, self::REFUND, self::COMBINED, self::MANUAL, self::EMANDATE_DEBIT];

    /**
     * Except combined recon type, we want to keep the analytics file
     * in a subfolder named as gateway_reconType. For combined recon type
     * we keep the files under gateway/ folder itself.
     */
    const DEFAULT_S3_PATH_RECON_TYPE = [self::COMBINED];

    // Output file path related constants
    const RECONCILIATION_OUTPUT = 'reconciliation_output';
    const TRANSACTION           = 'transaction';

    //
    // Used to define start_row for the MIS files.
    // Some of them have some random crap at the start of the file.
    //
    const DEFAULT_START_ROW = 1;

    /*************************
     * Internal Header Names
     *************************/

    const PAYMENT_ID             = 'payment_id';
    const REFUND_ID              = 'refund_id';
    const CARD_TYPE              = 'card_type';
    const CARD_LOCALE            = 'card_locale';
    const CARD_TRIVIA            = 'card_trivia';
    const CARD_DETAILS           = 'card_details';
    const GATEWAY_SERVICE_TAX    = 'gateway_service_tax';
    const GATEWAY_FEE            = 'gateway_fee';
    const GATEWAY_SETTLED_AT     = 'gateway_settled_at';
    const GATEWAY_AMOUNT         = 'gateway_amount';
    const ISSUER                 = 'issuer';
    const REFERENCE_NUMBER       = 'reference_number';
    const CUSTOMER_DETAILS       = 'customer_details';
    const CUSTOMER_ID            = 'customer_id';
    const CUSTOMER_NAME          = 'customer_name';
    const GATEWAY_PAYMENT_DATE   = 'gateway_payment_date';
    const ARN                    = 'arn';
    const GATEWAY_UTR            = 'gateway_utr';
    const ACCOUNT_DETAILS        = 'account_details';
    const ACCOUNT_NUMBER         = 'account_number';
    const ACCOUNT_TYPE           = 'account_type';
    const ACCOUNT_SUBTYPE        = 'account_subtype';
    const ACCOUNT_BRANCHCODE     = 'account_branchcode';
    const CREDIT_ACCOUNT_NUMBER  = 'credit_account_number';
    const AUTH_CODE              = 'auth_code';
    const GATEWAY_TRANSACTION_ID = 'gateway_transaction_id';
    const GATEWAY_REFERENCE_ID1  = 'gateway_reference_id1';
    const GATEWAY_REFERENCE_ID2  = 'gateway_reference_id2';
    const GATEWAY_PAYMENT_ID     = 'gateway_payment_id';
    const GATEWAY_UNIQUE_ID      = 'gateway_unique_id';

    const GATEWAY_TOKEN          = 'gateway_token';
    const GATEWAY_ERROR_CODE     = 'gateway_error_code';
    const GATEWAY_ERROR_DESC     = 'gateway_error_desc';
    const GATEWAY_STATUS_CODE    = 'gateway_status_code';

    const OUTPUT_FILE_SUFFIX        = '_analytics_recon_batch_output';
    const TRANSACTIONS_FILE_SUFFIX  = '_transactions_file';
    const DIRECTORY_PATH            = 'files/settlement';

    /*************************
     * Card types
     *************************/

    const CREDIT        = 'credit';
    const DEBIT         = 'debit';
    const DOMESTIC      = 'domestic';
    const INTERNATIONAL = 'international';

    /*********************
     * Instance objects
     *********************/

    protected $subReconciliator;
    protected $messenger;
    protected $app;
    protected $repo;

    protected $gateway;

    protected $batchId;

    /**
     * This variable indicates if we are in reconciliation code flow.
     * Currently this flag is being used to skip checksum validation
     * while creating Hitachi Unexpected payment via recon.
     * @var bool
     */
    public static $isReconRunning = false;

    public function __construct(string $gateway = null)
    {
        parent::__construct();

        $this->gateway = $gateway;

        $this->messenger = new Messenger;
    }

    /**
     * This is the start of the reconciliation. This is executed from the orchestrator.
     * For each file, it figures out which type of reconciliation is it (nodal, payment, refund, combined)
     * and calls the startReconciliation of the respective reconciliation type.
     *
     * @param array $allFilesContents
     * @return array $allSummaries Returns the summary of each file that has been reconciled.
     */
    public function startReconciliation(array $allFilesContents)
    {
        $allSummaries = [];

        foreach ($allFilesContents as $fileContents)
        {
            $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);

            // If unable to get the reconciliation type, just continue on to the next file.
            // An alert is raised in the function getReconciliationType in case of this.
            if ($reconciliationType === null)
            {
                continue;
            }

            $this->setSubReconciliator($reconciliationType);

            $summary = $this->subReconciliator->startReconciliation($fileContents);

            $allSummaries[] = $summary;
        }

        $this->trace->info(
            TraceCode::RECON_INFO_SUMMARY,
            $allSummaries
        );

        return $allSummaries;
    }

    /**
     * This is the start of the reconciliation. This is executed from the reconciliation
     * batch processor. For each file, it figures out which type of reconciliation it is
     * (nodal, payment, refund, combined) and calls the startReconciliation of the
     * respective reconciliation type.
     *
     * The logic here is exactly the same as in startReconciliate, except that we
     * pass the batch entity to the individual subreconciliators
     *
     * @param array $allFilesContents
     * @param Batch\Processor\Reconciliation $batchProcessor
     * @param string $source
     * @return array
     */
    public function startReconciliationV2(array $allFilesContents, Batch\Processor\Reconciliation $batchProcessor, string $source)
    {
        $batch = $batchProcessor->batch;

        $this->batchId = $batch ? $batch->getId() : null;

        $this->messenger->batch = $batch;

        $this->messenger->batchId = $this->batchId;

        foreach ($allFilesContents as $fileContents)
        {
            self::$isReconRunning = true;

            $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];

            // Check if this call is coming from batch service and set batch id accordingly
            if (isset($fileContents[Orchestrator::EXTRA_DETAILS][Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === true)
            {
                $reconciliationType = $fileContents[Orchestrator::EXTRA_DETAILS][Batch\Entity::CONFIG][RequestProcessor\Base::SUB_TYPE];

                $this->batchId = $fileContents[Orchestrator::EXTRA_DETAILS][Batch\Entity::CONFIG][Constants::BATCH_ID];

                $this->messenger->batchId = $this->batchId;
            }
            else
            {
                $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);

                if ($reconciliationType !== null)
                {
                    $this->updateBatchWithReconciliationType($batch, $reconciliationType, $extraDetails);
                }
            }

            // If unable to get the reconciliation type, just continue on to the next file.
            // An alert is raised in the function getReconciliationType in case of this.
            if ($reconciliationType === null)
            {
                continue;
            }

            $this->preProcessFileContents($fileContents, $reconciliationType);

            $this->setSubReconciliator($reconciliationType, $batch);

            $this->subReconciliator->setSource($source);

            try
            {
                $this->subReconciliator->startReconciliationV2($fileContents, $batchProcessor);
            }
            catch (\Throwable $e)
            {
                $tracePayload = [
                    'gateway'   => $this->gateway,
                    'batch_id'  => $this->batchId
                ];

                $this->trace->traceException($e, Logger::CRITICAL, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::BATCH_PROCESSING_ERROR,
                        'message'    => $e->getMessage(),
                        'gateway'    => $this->gateway,
                        'batch_id'   => $this->batchId,
                    ]);
            }
            finally
            {
                if (count(self::$forceAuthorizedPayments) > 0)
                {
                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code' => TraceCode::RECON_INFO_ALERT,
                            'message'    => 'Tried Force authorizing these failed payments',
                            'count'      => count(self::$forceAuthorizedPayments),
                            'gateway'    => $this->gateway,
                            'payments'   => self::$forceAuthorizedPayments,
                            'batch_id'   => $this->batchId
                        ]);
                }

                $data = $batchProcessor->getReconBatchOutputData();

                $this->setBatchFailureSummary($data, $batch);

                // Create the output file, and modify the $data array (passing by reference here)
                $this->generateReconOutputFile($batch, $data, $extraDetails);

                self::$isReconRunning = false;

                // Return the response to be sent back to batch service
                if (isset($extraDetails[Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === true)
                {
                    return $data;
                }
            }
        }

        //
        // For batches having scrooge refunds, we send batch processed summary
        // when last chunk of scrooge response gets processed
        //
        if (($batchProcessor->hasScroogeRefunds() === false) and
            (isset($extraDetails[Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === false))
        {
            $this->traceBatchProcessingSummary($batch);
        }

        return [];
    }

    /**
     * Creates an analytics output file corresponding to the current recon batch file,
     * This output file contains all valid rows of input MIS file and additional
     * columns i.e. recon_type, recon_status, error_msg, batch_id, attempts etc
     *
     * @param Batch\Entity $batch
     * @param array $data
     * @param array $extraDetails
     * @return array|void
     */
    protected function generateReconOutputFile(Batch\Entity $batch = null, array &$data, array $extraDetails)
    {
        $attempt = $batch ? $batch->getAttempts() : 1;

        $success = true;

        $this->getOutputWithRemovedBlackListedColumns($data, $this->batchId, $attempt, $success);

        if ($success === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code' => InfoCode::RECON_OUTPUT_FILE_GENERATION_SKIPPED,
                    'batch_id'  => $this->batchId,
                    'gateway'   => $this->gateway,
                ]
            );

            return;
        }

        $fileDetails = $this->getOutputFileNameAndFilePath($extraDetails, $attempt, $data);

        foreach ($fileDetails as $fileDetail)
        {
            $this->uploadReconOutputFiles($fileDetail, $batch);
        }
    }

    /**
     * @param $reconOutputData
     * @param $batchId
     * @param $attemptNumber
     * @param $success :  set to false if blacklisted columns not defined.
     *
     * Removes blacklisted columns if present. Adds batch_id, attempt_number column for each row.
     */
    protected function getOutputWithRemovedBlackListedColumns(&$reconOutputData, $batchId, $attemptNumber, &$success)
    {
        $blackListedColumns = $this->subReconciliator->getBlackListedColumnHeadersForOutputFile();

        if ($blackListedColumns === null)
        {
            $success = false;

            return;
        }

        foreach ($reconOutputData as &$row)
        {
            foreach ($blackListedColumns as $column)
            {
                unset($row[$column]);
            }

            $row['batch_id'] = $batchId;

            $row['attempt_number'] = $attemptNumber;
        }
    }

    /**
     * Creates file locally and formulates file name
     * using gateway, sheet name and batch attempts
     *
     * @param array $extraDetails
     * @param $attempt int
     * @param $outputData array
     * @return array
     */
    protected function getOutputFileNameAndFilePath(array $extraDetails, $attempt, &$outputData)
    {
        $sheetName = null;

        //
        // In case of excel file with multiple sheets, we should keep the recon output file
        // name different, else the previous sheet output file will be replaced by current one.
        // So here we are putting the sheet name to create filename in case of excel files.
        //
        if (empty($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]) === false)
        {
            $sheetName = $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME];

            $sheetName = '_' . strtolower(str_replace(' ', '_', $sheetName));
        }

        $analyticsOutputFileName = $this->batchId . $sheetName . self::OUTPUT_FILE_SUFFIX;

        $strDate = Carbon::now(Timezone::IST)->format('ymdHis');

        //
        // We want to make txn file name unique so as to avoid being replaced at s3 location.
        // Even in 1 second duration, we get multiple requests (from batch service for the
        // same Batch_ID) and thus timestamp is not enough, adding random string of 5 chars
        // to avoid same file name and replace issue.
        // i.e. reconciliation_output/transaction/EsDLvEJBSefq0H_sheet0_200519175324_8VwXt_transactions_file
        //
        $transactionsFileName = $this->batchId . $sheetName . '_' . $strDate . '_' . str_random(5) . self::TRANSACTIONS_FILE_SUFFIX;

        $dirPath = self::RECONCILIATION_OUTPUT . DIRECTORY_SEPARATOR . $this->gateway;

        if (isset($extraDetails[Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === true)
        {
            $reconciliationType = $extraDetails[Batch\Entity::CONFIG][RequestProcessor\Base::SUB_TYPE];
        }
        else
        {
            $reconciliationType = $this->getReconciliationType($extraDetails);
        }

        $dirPathForTransaction = self::RECONCILIATION_OUTPUT . DIRECTORY_SEPARATOR . self::TRANSACTION;

        if ((in_array($reconciliationType, self::DEFAULT_S3_PATH_RECON_TYPE, true) === false))
        {
            // Append _ReconType
            $dirPath .= '_' . $reconciliationType;
        }

        $outputFileName = $dirPath . DIRECTORY_SEPARATOR . $analyticsOutputFileName;

        $txnFileName = $dirPathForTransaction . DIRECTORY_SEPARATOR . $transactionsFileName;

        if ($attempt > 1)
        {
            //
            // After each retry, the output file is generated again. Need to append
            // attempt count, so as to avoid file overwrite in s3 bucket.
            //
            $outputFileName .= '_' . $attempt;
        }

        $txnFileDetails = $this->createTransactionsOutputFile($outputData, $transactionsFileName);

        $fileDetails = [
            [
                'file_name' => $txnFileName,
                'file_path' => $txnFileDetails['filepath'] ?? null,
                'count'     => $txnFileDetails['count'] ?? null,
                'suffix'    => self::TRANSACTIONS_FILE_SUFFIX
            ]
        ];

        // Batch service will be generating output files for recon request coming from them
        // So generate this output only when this flag is not set.
        if (isset($extraDetails[Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === false)
        {
            $analyticsFileDetails = $this->createAnalyticsOutputFile($outputData, $analyticsOutputFileName);

            $fileDetails[] =  [
                'file_name' => $outputFileName,
                'file_path' => $analyticsFileDetails['filepath'] ?? null,
                'count'     => $analyticsFileDetails['count'] ?? null,
                'suffix'    => self::OUTPUT_FILE_SUFFIX
            ];
        }

        return $fileDetails;
    }

    /**
     * Takes out specific data from output row and creates the file locally
     * @param array $outputData
     * @param string $transactionsFileName
     * @return array
     */
    protected function createTransactionsOutputFile(array &$outputData, string $transactionsFileName)
    {
        try
        {
            $txnData = $this->getTransactionData($outputData);

            if (count($txnData) === 0)
            {
                $this->trace->info(TraceCode::RECON_BATCH_TXN_FILE_DATA_EMPTY,
                    [
                        'info_code' => InfoCode::RECON_TXN_FILE_GENERATION_SKIPPED,
                        'file_name' => $transactionsFileName,
                        'gateway'   => $this->gateway,
                    ]
                );

                return [];
            }

            $filepath = $this->createCsvFile($txnData, $transactionsFileName, null, self::DIRECTORY_PATH);

            return [
                'filepath' => $filepath,
                'count'    => count($txnData),
            ];
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'info_code' => InfoCode::RECON_FAILED_TO_CREATE_TXN_FILE,
                    'message'   => $ex->getMessage(),
                    'file_name' => $transactionsFileName,
                    'gateway'   => $this->gateway,
                ]
            );

            return [];
        }
    }

    /**
     * Creates analytics file locally and returns filepath and count
     *
     * @param array $outputData
     * @param string $analyticsOutputFileName
     * @return array
     */
    protected function createAnalyticsOutputFile(array $outputData, string $analyticsOutputFileName)
    {
        try
        {
            $filepath = $this->createCsvFile($outputData, $analyticsOutputFileName, null, self::DIRECTORY_PATH);

            return [
                'filepath' => $filepath,
                'count'    => count($outputData),
            ];
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'info_code' => InfoCode::RECON_FAILED_TO_CREATE_ANALYTICS_FILE,
                    'message'   => $ex->getMessage(),
                    'file_name' => $analyticsOutputFileName,
                    'gateway'   => $this->gateway,
                ]
            );

            return [];
        }
    }

    /**
     * Creates filestore entity for output file and uploads to S3
     *
     * @param Batch\Entity $batch
     * @param array $fileDetails
     */
    protected function uploadReconOutputFiles(array $fileDetails, Batch\Entity $batch = null)
    {
        $fileName = $fileDetails['file_name'];

        $filePath = $fileDetails['file_path'];

        if ($filePath === null)
        {
            return;
        }

        $count = $fileDetails['count'];

        // Based on the file type, use the trace code and filestore type
        if ($fileDetails['suffix'] === self::OUTPUT_FILE_SUFFIX)
        {
            $infoCode  =  InfoCode::RECON_ATTEMPT_TO_CREATE_OUTPUT_FILE;
            $type      = FileStore\Type::RECONCILIATION_BATCH_ANALYTICS_OUTPUT;
            $traceCode = TraceCode::RECON_BATCH_ANALYTICS_OUTPUT_FILE;
        }
        else
        {
            $infoCode  =  InfoCode::RECON_ATTEMPT_TO_CREATE_TXN_FILE;
            $type      = FileStore\Type::RECONCILIATION_BATCH_TXN_FILE;
            $traceCode = TraceCode::RECON_BATCH_TXN_FILE;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => $infoCode,
                'batch_id'  => $this->batchId,
                'row_count' => $count,
                'file_name' => $fileName,
            ]
        );

        $creator = new FileStore\Creator;

        $extension = FileStore\Format::CSV;

        try
        {
            $creator->localFilePath($filePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$extension][0])
                    ->name($fileName)
                    ->extension($extension)
                    ->type($type)
                    ->additionalParameters(['ACL' => 'bucket-owner-full-control']);

            // $batch object is null when request comes from Batch Service
            if ($batch !== null)
            {
                $creator->entity($batch);
            }

            $fileStoreEntity = $creator->save()->get();
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_OUTPUT_FILE_CREATION_FAILED,
                [
                    'file_name' => $fileName,
                    'batch_id'  => $this->batchId,
                    'gateway'   => $this->gateway,
                ]);

            // Delete local file and return.
            (new FileProcessor)->deleteFileLocally($filePath);

            return;
        }

        // File uploaded successfully
        $traceData = [
            'file_id'   => $fileStoreEntity['id'],
            'file_name' => $fileStoreEntity['name'],
            'batch_id'  => $this->batchId,
            'gateway'   => $this->gateway,
        ];

        $this->trace->info($traceCode, $traceData);

        // Delete local file, as it has been upload to filestore (s3) now.
        (new FileProcessor)->deleteFileLocally($filePath);
    }

    /**
     * Sets failure count summary (if any recon failure)
     * in failure_reason column of batch entity.
     * Also modifies corresponding recon status
     * description and error code description
     *
     * @param Batch\Entity $batch
     * @param $data
     */
    protected function setBatchFailureSummary(&$data, Batch\Entity $batch = null)
    {
        $failureSummary = [];

        foreach ($data as &$row)
        {
            $reconStatus = $row[SubReconciliate::RECON_STATUS];

            if ($reconStatus === InfoCode::RECON_FAILED)
            {
                $errorCode = $row[SubReconciliate::RECON_ERROR_MSG];

                $prevCount = $failureSummary[$errorCode] ?? 0;

                $failureSummary[$errorCode] = $prevCount + 1;

                // Set error description
                $errorMsg = Constants::RECON_PUBLIC_DESCRIPTIONS[$errorCode] ?? $errorCode;

                $row[SubReconciliate::RECON_ERROR_MSG] = $errorMsg;
            }

            // Set recon status description
            $statusDescription = Constants::RECON_PUBLIC_DESCRIPTIONS[$reconStatus] ?? $reconStatus;

            $row[SubReconciliate::RECON_STATUS] = $statusDescription;
        }

        if ((empty($failureSummary) === false) and ($batch !== null))
        {
            $batch->setFailureReason(json_encode($failureSummary));
        }
    }

    /**
     * This should be implemented in the child class if the gateway needs to
     * look at only certain sheets present in the excel file and not all of them.
     */
    public function getSheetNames(array $fileDetails = [])
    {
        return [];
    }

    /**
     * This should be implemented in the child class if the gateway needs to
     * look at certain columns to identify whether the row is payment or refund row/header.
     */
    public function getKeyColumnNames(array $fileDetails = [])
    {
        return [];
    }

    /**
     * This should be implemented in the child class if the gateway requires certain
     * files to be excluded from doing the reconciliation.
     *
     * @param array $fileDetails
     * @return bool
     */
    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        return false;
    }

    /**
     * This should be implemented in the child class if the gateway sends zip files
     * which are password protected.
     *
     * @param array $fileDetails
     * @return null
     */
    public function getReconPassword($fileDetails)
    {
        return null;
    }

    public function shouldUse7z($zipFileDetails)
    {
        return false;
    }

    public function getStartRow($fileDetails)
    {
        return self::DEFAULT_START_ROW;
    }

    /**
     * This should be implemented in the child class if the gateway
     * sends CSV files which has a delimiter other than `,`
     *
     * @return string
     */
    public function getDelimiter()
    {
        return ',';
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        return $fileDetails;
    }

    /**
     * Thus can be overridden from the child class.
     * If not overriden, it fetches the mapping from FileProcessor::FILE_TYPES_MAPPINGS
     *
     * @param  string $mimeType
     * @return string
     */
    public function getFileType(string $mimeType): string
    {
        return get_key_from_subarray_match($mimeType, FileProcessor::FILE_TYPES_MAPPINGS);
    }

    /**
     * Stub method to be overriden by child classes to provide the reconciliation
     * type for the particular gateway based on the fileName
     *
     * @param  string $fileName Name of the file or sheet in case of excel
     */
    protected function getTypeName($fileName)
    {
        return;
    }

    /**
     * Gets the reconciliation type by either the sheet name in case of excel files
     * or by the file name in case of csv files.
     *
     * @param $extraDetails
     * @return mixed
     */
    protected function getReconciliationType($extraDetails)
    {
        $fileName = $this->getFileName($extraDetails);

        $fileName = strtolower($fileName);

        // The method is present in child class since different gateways have
        // different sheet names/file names for reconciliation types.
        $reconciliationType = $this->getTypeName($fileName);

        // Ideally, should never come here.
        if ((in_array($reconciliationType, self::VALID_RECON_TYPES, true) === false))
        {
            $traceData = [
                'trace_code'            => TraceCode::RECON_PARSE_ERROR,
                'message'               => 'Unable to figure out the reconciliation type. Skipping this file.',
                'reconciliation_type'   => $reconciliationType,
                'extra_details'         => $extraDetails,
                'gateway'               => $this->gateway
            ];

            // No slack alerts for unexpected MIS files, just trace it
            if ($reconciliationType !== self::INVALID_RECON_TYPE)
            {
                $this->messenger->raiseReconAlert($traceData);
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    $traceData
                );
            }

            return null;
        }

        return $reconciliationType;
    }

    /**
     * Sets the subtype for the batch based on the reconciliation sub type. However if
     * the file is an excel with multiple sheets, we always set the sub_type to combined.
     *
     * @param  Batch\Entity $batch              Batch entity for recon
     * @param  string       $reconciliationType Recon type determined for the file
     * @param  array        $extraDetails       Extra file metadata
     */
    protected function updateBatchWithReconciliationType(
        Batch\Entity $batch,
        string $reconciliationType,
        array $extraDetails)
    {
        if ($reconciliationType === self::EMANDATE_DEBIT)
        {
            $batch->setSubType($reconciliationType);
        }
        else if (($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_TYPE] === FileProcessor::EXCEL) and
            ($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_COUNT] > 0))
        {
            $batch->setSubType(self::COMBINED);
        }
        else
        {
            $batch->setSubType($reconciliationType);
        }
    }

    /**
     * @param  array  $extraDetails  File metad data
     * @return string               name of the file
     */
    protected function getFileName(array $extraDetails): string
    {
        //
        // For excel recon files, we consider the sheet name if present as the file name.
        //
        if (isset($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]) === true)
        {
            return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME];
        }

        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    public function getReconciliationTypeFromFileName($fileName)
    {
        $fileName = strtolower($fileName);

        return $this->getTypeName($fileName);
    }

    protected function setSubReconciliator($reconciliationType, Batch\Entity $batch = null)
    {
        $subReconciliatorClassName = $this->getSubReconciliatorClassName($reconciliationType);

        $this->subReconciliator = new $subReconciliatorClassName($this->gateway, $batch);
    }

    protected function getSubReconciliatorClassName($reconciliationType)
    {
        // Parent namespace should be something like - Reconciliator/Axis
        $parentNamespace = $this->getParentNamespace();

        // SubReconciliator class name should be something like - Reconciliator/Axis/PaymentReconciliate
        $subReconciliatorClassName = $parentNamespace . '\\' . 'SubReconciliator' . '\\'
                                    . studly_case($reconciliationType)
                                    . 'Reconciliate';

        return $subReconciliatorClassName;
    }

    protected function getParentNamespace()
    {
        // Gets the namespace from the called class, by removing the last part of the FQCN.
        return join('\\', explode('\\', get_called_class(), -1));
    }

    public function getColumnHeadersForType($type)
    {
        return [];
    }

    /**
     * This method returns the number of lines to be skipped from end or from
     * beginning while reading csv files. Should be overriden by any gateway
     * specific child classes.
     *
     * @param array $fileDetails
     *
     * @return array number of lines to skip from top and bottom
     */
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 0,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    // Sends recon batch processing summary
    public function traceBatchProcessingSummary(Batch\Entity $batch)
    {
        $outputFileIds = $this->getReconOutputFileIds($batch);

        $fileName = basename($batch->latestFileByType(FileStore\Type::RECONCILIATION_BATCH_INPUT)->location);

        $originalFileName = str_replace('_' . $batch->getId(), '', $fileName);

        $summary = [
            'info_code'         => InfoCode::RECON_PROCESSED_BATCH_SUMMARY,
            'file'              => $originalFileName,
            'output_file_id'    => $outputFileIds['output_file_ids'],
            'txn_file_id'       => $outputFileIds['txn_file_ids'],
            'total_count'       => $batch->getTotalCount(),
            'success_count'     => $batch->getSuccessCount(),
            'failure_count'     => $batch->getFailureCount(),
            'failure_reason'    => $batch->getFailureReason(),
            'batch_id'          => $batch->getDashboardEntityLinkForSlack(),
            'gateway'           => $batch->getGateway()
        ];

        //Check if recon request was made through dashboard
        $isDashboardRequest = $this->app['basicauth']->isDashboardApp();

        if ($isDashboardRequest === true)
        {
            $summary['dashboard_user'] = $this->getInternalUsernameOrEmail();
        }

        $skipSlack = in_array($batch->getGateway(), Service::BATCH_SUMMARY_SKIP_GATEWAYS, true);

        // Raise recon info/alert with the batch processing summary
        if ($batch->getFailureCount() > 0)
        {
            $this->messenger->setSkipSlack($skipSlack)->raiseReconAlert($summary);
        }
        else
        {
            unset($summary['failure_reason']);

            $this->messenger->setSkipSlack($skipSlack)->raiseReconInfo($summary);
        }
    }

    /**
     * Get the corresponding output file IDs and
     * transaction file IDs for this batch
     *
     * @param Batch\Entity $batch
     * @return array
     */
    protected function getReconOutputFileIds(Batch\Entity $batch)
    {
        $outputFiles = $batch->filesByType(FileStore\Type::RECONCILIATION_BATCH_ANALYTICS_OUTPUT);
        $txnFiles = $batch->filesByType(FileStore\Type::RECONCILIATION_BATCH_TXN_FILE);

        //
        // In case of excel file having 2 or more sheets, those many batch output files
        // get generated. So need to put all the output file_ids in the trace
        //
        $outputFileIds = [];

        if (count($outputFiles) === 1)
        {
            $outputFileIds = $outputFiles->first()->id;
        }
        else if (count($outputFiles) > 1)
        {
            foreach ($outputFiles as $file)
            {
                $outputFileIds[] = $file->id;
            }
        }

        $txnFileIds = [];

        if (count($txnFiles) === 1)
        {
            $txnFileIds = $txnFiles->first()->id;
        }
        else if (count($txnFiles) > 1)
        {
            foreach ($txnFiles as $file)
            {
                $txnFileIds[] = $file->id;
            }
        }

        return [
            'output_file_ids' => $outputFileIds,
            'txn_file_ids'    => $txnFileIds
        ];
    }

    /**
     * Iterates over recon output data, and returns another array data, which
     * contains transaction entity details corresponding to the recon entity i.e.
     * payment or refund.
     * Un-sets txn file related fields in output row
     *
     * This data will be used to create transaction data file to be pushed to s3 and
     * further used to transaction unrecon ageing.
     *
     * @param array $outputData
     * @return array
     */
    protected function getTransactionData(array &$outputData)
    {
        $transactionsData = [];

        foreach ($outputData as &$row)
        {
            // Check recon status for "Already Reconciled" string
            $alreadyReconciled = Constants::RECON_PUBLIC_DESCRIPTIONS[InfoCode::ALREADY_RECONCILED];

            if ((empty($row[SubReconciliate::RZP_TXN_ID]) === true) or
                ($row[SubReconciliate::RECON_STATUS] === $alreadyReconciled))
            {
                //
                // txn_id not set for this row. Possible reason could be errors like
                // 'Payment/Refund ID not found for this row', or the Payment/refund
                // do not exist in our DB etc.
                // We dont want to include such data in the output file
                //
                // We do not want to push already reconciled txn again, so excluded.
                //
                $this->unsetTxnFileRelatedColumns($row);

                // if this extra column was added by Finops, need to unset
                // so that output file column format remain consistent.
                unset($row[PaymentReconciliate::RZP_FORCE_AUTH_PAYMENT]);

                continue;
            }

            $txn = [
                SubReconciliate::RZP_TXN_ID             => $row[SubReconciliate::RZP_TXN_ID],
                SubReconciliate::RECON_TYPE             => $row[SubReconciliate::RECON_TYPE],
                SubReconciliate::RECON_ENTITY_ID        => $row[SubReconciliate::RECON_ENTITY_ID],
                SubReconciliate::RZP_TXN_AMOUNT         => $row[SubReconciliate::RZP_TXN_AMOUNT],
                SubReconciliate::RZP_TXN_CURRENCY       => $row[SubReconciliate::RZP_TXN_CURRENCY],
                SubReconciliate::RZP_GATEWAY            => $row[SubReconciliate::RZP_GATEWAY],
                SubReconciliate::RZP_GATEWAY_ACQUIRER   => $row[SubReconciliate::RZP_GATEWAY_ACQUIRER],
                SubReconciliate::RZP_IS_RECONCILED      => $row[SubReconciliate::RZP_IS_RECONCILED],
                SubReconciliate::RZP_RECONCILED_AT      => $row[SubReconciliate::RZP_RECONCILED_AT],
                SubReconciliate::RZP_IS_RECONCILIABLE   => $row[SubReconciliate::RZP_IS_RECONCILIABLE],
                SubReconciliate::RECON_ERROR_MSG        => $row[SubReconciliate::RECON_ERROR_MSG],
                SubReconciliate::RZP_TXN_CREATED_AT     => $row[SubReconciliate::RZP_TXN_CREATED_AT],
                SubReconciliate::BATCH_ID               => $row[SubReconciliate::BATCH_ID],
                SubReconciliate::RZP_MERCHANT_ID        => $row[SubReconciliate::RZP_MERCHANT_ID],
                SubReconciliate::RZP_TERMINAL_ID        => $row[SubReconciliate::RZP_TERMINAL_ID],
                SubReconciliate::RZP_SETTLED_BY         => $row[SubReconciliate::RZP_SETTLED_BY],
                SubReconciliate::RZP_METHOD             => $row[SubReconciliate::RZP_METHOD],
                SubReconciliate::PROCESSED_AT           => $this->getTimeInEpochFormat($row[SubReconciliate::PROCESSED_AT]),
                SubReconciliate::TAG_1                  => '',
                SubReconciliate::TAG_2                  => '',
                SubReconciliate::TAG_3                  => '',
            ];

            $transactionsData[] = $txn;

            $this->unsetTxnFileRelatedColumns($row);

            // if this extra column was added by Finops, need to unset
            // so that output file column format remain consistent.
            unset($row[PaymentReconciliate::RZP_FORCE_AUTH_PAYMENT]);
        }

        return $transactionsData;
    }

    /**
     * Unsets the transaction file related additional field
     * @param array $outputRow
     */
    protected function unsetTxnFileRelatedColumns(array &$outputRow)
    {
        foreach (SubReconciliate::TXN_FILE_ADDITIONAL_FIELDS as $field)
        {
            unset($outputRow[$field]);
        }
    }

    protected function getTimeInEpochFormat(string $time, $format = 'Y-m-d H:i:s')
    {
        return Carbon::createFromFormat($format, $time, Timezone::IST)->timestamp;
    }

    /**
     * For specific gateways, we need to modify
     * the contents of the mis rows, before
     * starting recon for each row.
     * Currently only implemented for FirstData.
     *
     * @param array $fileContents
     * @param string $reconciliationType
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        return;
    }

    protected function getRefundIdFromScrooge(array $input, $gateway)
    {
        $responses = [];

        foreach (array_chunk($input, self::SCROOGE_CHUNK_SIZE) as $chunks)
        {
            try {
                $request = [
                    'gateway'       => $gateway,
                    'query_data'    => $chunks,
                ];

                $scroogeResponse = $this->app['scrooge']->getRefundsFromPaymentIdAndGatewayId($request);

                foreach ($scroogeResponse['body']['data'] as $key => $value)
                {
                    $responses[$key] = $value;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REFUND_RECON_SCROOGE_CALL_FAILED,
                    [
                        'info_code'     => InfoCode::REFUND_RECON_SCROOGE_JOB_FAILURE_EXCEPTION,
                        'gateway'       => $gateway,
                        'batch_id'      => $this->batchId,
                    ]
                );
            }
        }

        return $responses;
    }
}
