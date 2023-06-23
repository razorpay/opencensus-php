<?php

namespace RZP\Models\Batch\Processor;

use Mail;
use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Encryption;
use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Feature;
use RZP\Encryption\Type;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Refund;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Exception\BadRequestException;
use RZP\Models\Batch\Type as BatchType;
use Symfony\Component\HttpFoundation\File\File;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Exception\BadRequestValidationFailureException;

class Base extends BaseModel\Core
{
    use FileHandlerTrait {
        parseExcelSheets as parentParseExcelSheets;
    }

    /**
     * Lock wait timeout for batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 2500;

    /**
     * Max number of parsed rows that should
     * be shown to merchant for reference
     */
    const MAX_PARSED_ROWS    = 3;

    /**
     * Max number of parsed rows,
     * specifically for batch refund validate API,
     * that should be shown to merchant for reference
     */
    const MAX_PARSED_ROWS_FOR_REFUND = 10;

    /**
     * Map for the file type of batch entity and the
     * corresponding path where they should be stored.
     */
    const FILE_TYPE_PREFIX_MAP = [
        FileStore\Type::BATCH_INPUT     => Batch\Entity::INPUT_FILE_PREFIX,
        FileStore\Type::BATCH_VALIDATED => Batch\Entity::VALIDATED_FILE_PREFIX,
        FileStore\Type::BATCH_OUTPUT    => Batch\Entity::OUTPUT_FILE_PREFIX,
    ];

    const EXCEL_FORMULAE_INITIATOR = [
        "=",
        "@",
        "\"=",
        "\"@"
    ];

    // Additional output keys
    const FILE_ID           = 'file_id';
    const SIGNED_URL        = 'signed_url';

    /**
     * The batch entity which is being processed
     * @var Batch\Entity
     */
    public $batch;

    /**
     * The MUTEX instance
     */
    protected $mutex;

    /**
     * The merchant instance
     *
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * Additional parameters from request or query.
     *
     * @var array
     */
    protected $params = [];

    /**
     * @var Settings\Accessor
     */
    protected $settingsAccessor;

    /**
     * Holds local file path of input & output file respectively.
     * They are re-used in the flow.
     * E.g.
     * - sending mails with attachment,
     * - un-linking post processing etc..
     */
    protected $inputFileLocalPath;
    protected $outputFileLocalPath;

    /**
     * Holds filetype of input & output file respectively.
     */
    protected $inputFileType;
    protected $outputFileType;

    /**
     * Override from child processor to use legacy library.
     * @var boolean
     */
    protected $useSpreadSheetLibrary = true;

    /**
     * Holds recon batch data to be sent to Scrooge Service
     * @var
     */
    protected $scroogeDispatchData;

    /**
     * Holds Recon batch output data, which it is used to
     * generate the output file
     * @var
     */
    protected $reconBatchOutputData;

    /**
     * Flag to check if encryption/decryption is required or not
     * @var
     */
    protected $isEncrypted;

    /**
     * Holds delimiter for output text file
     *
     * @var string
     */
    protected $delimiter = '|';

    protected $ignoreHeaders = false;

    protected $amountType = null;

    public function __construct(Batch\Entity $batch = null)
    {
        parent::__construct();

        $this->mutex            = $this->app['api.mutex'];
        $this->batch            = $batch;
        $this->isEncrypted      = false;

        if($batch !== null)
        {
            $this->merchant = $batch->merchant;
            $this->settingsAccessor = Settings\Accessor::for($this->batch, Settings\Module::BATCH);
            $this->app['basicauth']->setMerchant($this->merchant);

            // Indicates that the request is being executed by a batch upload flow
            $this->app['basicauth']->setBatch($batch);
        }

    }

    public function setParams(array $params = null)
    {
        //
        // TODO: Remove this method, $params member variable and it's usage in queue class.
        // To maintain backward compatibility with old queue jobs.
        // Old queue job will have $params as null in Job\Batch class.
        //

        $this->params = $params ?: [];

        return $this;
    }

    public function getBatchContext(array $config): array
    {
        $batchContext                        = [];
        $batchContext[Batch\Entity::TYPE]    = $this->batch->getType();
        $batchContext[Batch\Constants::DATA] = $config;

        return $batchContext;
    }

    /**
    * Create flow: Stores input file to file store, does parsing and basic
    * validation and then saves the batch with its input configurations.
    *
    * @param array $input
    */
    public function storeInputFileAndSaveBatchWithSettings(array $input)
    {
        // encrypt sensitive fields before saving file
       $this->encryptBatchSensitiveFields($input);

        //
        // We upload the file and create file store entity first. As of now
        // the file store entity gets created without batch entity association.
        // We do this outside of transaction. We do this outside transaction
        // as if there is some parsing error in file we will still have file
        // store entity to refer to.
        //
        // This follows a transaction where parsing and basic validation of file
        // happens and then we create the batch entity and associated above
        // created file store entity with this batch and save both of them.
        //

        //
        // For file upload, we throw an error in Batch/Validator::validateEntries.
        // This attribute is used in this validator. It's not stored though.
        //
        if (isset($input[Batch\Entity::FILE]) === true)
        {
            $this->batch->setCreatedByFileUpload(true);
        }

        // For new flow, the file_store entity referenced by `file_id` in input
        // gets associated with this batch
        list($ufhFile, $entries) = $this->saveInputFileAndValidateEntries($input);

        $this->startworkflowIfApplicable($input['type'], $ufhFile);

        // if batch is migrated to new batch service
        // just return the ufhFile and do not save batches and files entity.
        if ($this->shouldSendToBatchService())
        {
            return $ufhFile;
        }

        $this->updateBatchPostValidation($entries, $input);

        $ufhFile->entity()->associate($this->batch);

        $this->repo->transaction(function () use ($ufhFile, $input)
        {
            $this->repo->saveOrFail($this->batch);

            $this->repo->saveOrFail($ufhFile);

            $this->saveSettings($input);
        });

        return $ufhFile;
    }

    /**
     * Validate flow: Stores and validate the input file.
     * Returns file id, signed url, preview(first few parsed entries) etc.
     *
     * @param  array  $input
     * @return array
     */
    public function storeAndValidateInputFile(array $input): array
    {
        list($inputUfhFile, $entries) = $this->saveInputFileAndValidateEntries($input);

        $validatedUfhFile = $this->createSetOutputFileAndSave($entries, FileStore\Type::BATCH_VALIDATED);

        $response = $this->getValidatedEntriesStatsAndPreview($entries);

        if ($this->shouldSkipValidateInputFile())
        {
            $response += $this->getFileIdAndSignedUrlFromFileEntity($inputUfhFile);
        }
        else
        {
            $response += $this->getFileIdAndSignedUrl($validatedUfhFile);
        }
        if ($response[Batch\Constants::ERROR_COUNT] > 0)
        {
            $this->trace->info(TraceCode::ERROR_IN_VALIDATING_BATCH_FILE,
                [
                    self::FILE_ID                => $response[self::FILE_ID],
                    Batch\Constants::ERROR_COUNT => $response[Batch\Constants::ERROR_COUNT],
                ]
            );
        }

        $this->deleteLocalFiles();

        return $response;
    }

    public function setScroogeDispatchData(array $data)
    {
        // Do nothing from Base class. This is handled in Reconciliation.php

        return;
    }

    public function setReconBatchOutputData(array $data)
    {
        // Do nothing from Base class. This is handled in Reconciliation.php

        return;
    }
    private function startWorkflowIfApplicable($batchType, $ufhFile)
    {
        $fileId = $ufhFile->getPublicId();

        if (in_array($batchType, array_keys(BatchType::$workflowApplicableBatchTypes)) === true)
        {
            $this->app['request']->merge(['file_id' => $fileId]);

            $this->trace->info(TraceCode::BATCH_WORKFLOW, ['file_id' => $fileId]);

            $signedUrl = (new FileStore\Service)->fetchFileSignedUrlById($fileId);

            $this->app['workflow']->setEntityAndId('file_store', 'file_' . $fileId)
                 ->setPermission(BatchType::$workflowApplicableBatchTypes[$batchType])
                 ->setMakerFromAuth(false)
                 ->handle([], ['status' => $signedUrl]);
        }
    }

    /**
     * Saves input file and validates entries.
     * Returns the ufh file and parsed entries.
     *
     * @param  array  $input
     * @return array
     */
    protected function saveInputFileAndValidateEntries(array $input): array
    {
        // Here $ufhFile is the input file_store instance upload by merchant.
        // $ufhFile has no entity associated with it and has type = `batch_input`
        $ufhFile = $this->getInputFile($input);

        $this->inputFileLocalPath = $ufhFile->getFullFilePath();
        $this->inputFileType      = $ufhFile->getType();

        // $entries here might have extra error_code and error_description headers
        $entries = $this->validateInputFileEntries($input);

        return [$ufhFile, $entries];
    }

    /**
     * TODO: add support for other than csv formats also
     */
    protected function encryptBatchSensitiveFields(array $input)
    {
        $type = $input['type'];

        if (in_array($type, Batch\Type::$haveSensitiveData, true) === false)
        {
            return;
        }

        $file = $input['file'];

        $ext = $file->getClientOriginalExtension();

        if ($ext !== FileStore\Format::CSV)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_SERVICE_ERROR, 'Invalid file type for batch having sensitive headers, only csv file type allowed');
        }

        $rows = $this->parseCsvFile($file);

        $headings = $rows[0];

        $sensitiveHeadersIndexes = [];

        foreach(Header::HEADER_MAP[$type][Header::SENSITIVE_HEADERS] as $sensitiveHeader)
        {
            $index = array_search($sensitiveHeader, $headings);

            if ($index != false)
            {
                array_push($sensitiveHeadersIndexes, $index);
            }
        }

        $rowsToWrite = [];

        $aesCrypto = new AESCrypto();

        foreach ($rows as $idx => $row)
        {
            // skip encryption for first row i.e. headings
            if ($idx !== 0)
            {
                foreach($sensitiveHeadersIndexes as $index)
                {
                    if (empty($row[$index]) === false)
                    {
                        $row[$index] = $aesCrypto->encryptString($row[$index]);
                    }
                }
            }

            array_push($rowsToWrite, $row);
        }

        $myFile = fopen($file->path(), 'w');

        foreach ($rowsToWrite as $rowToWrite) {
            fputcsv($myFile, $rowToWrite);
        }

        fclose($myFile);
    }

    /**
     * Validate flow: Returns stats(success/error count etc) and preview of
     * parsed and validated entries.
     *
     * @param  array  $entries
     * @return array
     */
    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $correctEntries = array_filter($entries, function($entry)
        {
            return (isset($entry[Batch\Header::ERROR_CODE]) === false);
        });

        $maxRowsToParse = ($this->batch->getType() === Refund\Constants::REFUND) ? self::MAX_PARSED_ROWS_FOR_REFUND : self::MAX_PARSED_ROWS;

        $previewData = array_slice($correctEntries, 0, $maxRowsToParse);

        $this->removeErrorColumnsFromEntries($previewData);

        $response = [
            Batch\Constants::PROCESSABLE_COUNT => count($correctEntries),
            Batch\Constants::ERROR_COUNT       => count($entries) - count($correctEntries),
            Batch\Constants::PARSED_ENTRIES    => $previewData,
        ];

        //
        // only if speed column exist, return the respone for it,
        // speed column is only introduced in batch refunds
        //
        if(array_key_exists(Batch\Header::SPEED, $entries[0]) === true)
        {
            //
            // add speed related details in the response
            //
            $normalSpeedCount = $this->getSpeedCount($correctEntries, Refund\Constants::NORMAL);
            $optimumSpeedCount = $this->getSpeedCount($correctEntries, Refund\Constants::OPTIMUM);
            $defaultSpeedCount = count($correctEntries) - ($normalSpeedCount + $optimumSpeedCount);

            $speedCount = array(
                Refund\Constants::NORMAL    => $normalSpeedCount,
                Refund\Constants::OPTIMUM   => $optimumSpeedCount,
                Refund\Constants::DEFAULT   => $defaultSpeedCount
            );

            $response += [Refund\Constants::SPEED_COUNT => $speedCount];
        }

        return $response;
    }

    protected function getSpeedCount(array $entries, string $speed): int
    {
        $count = count(array_filter($entries, function($entry) use ($speed)
        {
            return strtolower($entry[Batch\Header::SPEED]) === $speed;
        }));

        return $count;
    }

    /**
     * - If file_store entity is is passed in input, fetches file
     *   from s3 and saves it in filestore/batch/upload folder in local.
     *
     * - If file is uploaded (contains file object in $input)
     *   a file_store entity is created with batch_input as type
     *   which uploads the file in filestore/batch/upload
     *   folder in s3 too. The original file is saved in
     *   filestore/batch/upload folder in local.
     *
     * @param  array $input
     *
     * @return FileStore\Entity
     */
    protected function getInputFile(array $input): FileStore\Entity
    {
        if (isset($input[Batch\Entity::FILE_ID]) === true)
        {
            $inputFileId = $input[Batch\Entity::FILE_ID];

            // Download the file and store in local
            $accessor = new FileStore\Accessor;

            $accessor->id($inputFileId)
                     ->merchantId($this->merchant->getId())
                     ->getFile();

            $this->performDecryptionIfApplicable($accessor->getFile());

            return $accessor->get();
        }

        $inputFile = $input[Batch\Entity::FILE];

        // Saves the merchant uploaded input file with file type
        // as `batch_input` and no entity associated with it
        $ufh = $this->saveInputFile($inputFile);

        $ufhFile = $ufh->getFileInstance();

        $this->performDecryptionIfApplicable($ufh->getFullFilePath());

        return $ufhFile;
    }

    protected function performDecryptionIfApplicable($fileToBeDecrypted)
    {
        if ($this->shouldDecrypt() === true)
        {
            $type = Type::AES_ENCRYPTION;

            $params = [
                'mode'   => \phpseclib\Crypt\Base::MODE_CBC,
                'secret' => $this->secret,
            ];

            $encryptionHandler = new Encryption\Handler($type, $params);

            $encryptionHandler->decryptFile($fileToBeDecrypted);

            $this->trace->info(TraceCode::BATCH_FILE_DECRYPTION,
                [
                    $this->batch->getType(),
                ]);
        }
    }

    protected function saveSettings(array $input)
    {
        $config = $input[Batch\Entity::CONFIG] ?? [];

        // Temporary: For payout type batch captures user email to be used later to send processed file to.
        if ($this->batch->isPayoutType() === true)
        {
            $user = $this->app->basicauth->getUser();
            $config['user'] = [
                'name'  => $user->getName(),
                'email' => $user->getEmail(),
            ];
        }

        if (empty($config) === false)
        {
            $this->settingsAccessor->upsert($config)->save();
        }
    }

    /**
     * Checks if the batch can be processed. If yes, sets the processing flag
     * and calls the main process method. In other case throws an exception.
     * We perform the entire operation inside a mutex lock, so that concurrent
     * process requests are handled successfully. We also validate after doing a
     * data reload for the batch entity so that there is no chance of concurrent
     * requests processing the same batch.
     */
    public function validateAndProcess()
    {
        $this->mutex->acquireAndRelease(
            $this->batch->getId(),
            function ()
            {
                $this->batch->reload();

                $this->batch->getValidator()->validateIfProcessable();

                $this->batch->setProcessing(true);

                $this->repo->saveOrFail($this->batch);

                $this->process();
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function process()
    {
        try
        {
            $this->trace->info(TraceCode::BATCH_FILE_PROCESSING, $this->batch->toArrayTraceAll());

            $this->performPreProcessingActions();

            $this->parseAndProcessEntries();

            $this->setStatusAfterSuccessfulProcessing();
        }
        catch (\Throwable $ex)
        {
            $this->handleProcessingException($ex);
        }
        finally
        {
            $this->postProcess();
        }
    }

    protected function performPreProcessingActions()
    {
        $this->increaseAllowedSystemLimits();

        $this->batch->incrementAttempts();

        $this->resetBatchAttributes();

        $this->downloadAndSetInputFile();
    }

    protected function resetBatchAttributes()
    {
        //
        // We need to do this since sometimes we might re-run (retry) a batch.
        // The success_count and failure_count should be 0 since these will be
        // filled again on retry run of the batch. The idempotency needs to be
        // handled by the individual batch types for retries.
        //

        $this->batch->setSuccessCount(0);
        $this->batch->setFailureCount(0);
        $this->batch->unsetFailureReason();
        $this->batch->unsetProcessedCount();

        //
        // We are saving batch here, because in case batch is retried,
        // we need to set processed_count to 0 in db also, so that incrementing will start from 0.
        //
        $this->batch->saveOrFail();
    }

    protected function parseAndProcessEntries()
    {
        $entries = $this->parseFileAndCleanEntries($this->inputFileLocalPath);

        $this->processEntries($entries);

        $this->postProcessEntries($entries);

        $this->createSetOutputFileAndSave($entries);
    }

    /**
     * Processes all the entries of given batch.
     *
     * @param array $entries
     */
    protected function processEntries(array & $entries)
    {
        // Creation/validation step must ensure in general that there are entries to be processed
        assertTrue(count($entries) > 0, 'Error in processing batch, no entries read to process');

        $dimensions = $this->batch->getMetricDimensions();

        $this->trace->histogram(Batch\Metric::BATCH_PROCESSED_ROWS_TOTAL,
                                $this->batch->getTotalCount(),
                                $dimensions);

        foreach ($entries as $index => & $entry)
        {
            $entryTracePayload = $entry;

            $this->removeCriticalDataFromTracePayload($entryTracePayload);

            $tracePayload = $this->batch->toArrayTrace(
                [],
                [
                    'row_index' => $index,
                    'row'       => $entryTracePayload,
                ]);

            try
            {
                $timeStarted = millitime();

                $this->trace->debug(TraceCode::BATCH_PROCESSING_ENTRY, $tracePayload);

                $this->processEntry($entry);

                if ($this->resetErrorOnSuccess() === true)
                {
                    // Set errors as null

                    $entry[Batch\Header::ERROR_CODE]        = null;
                    $entry[Batch\Header::ERROR_DESCRIPTION] = null;
                }

                $timeTaken = millitime() - $timeStarted;

                //  status will be the previous status as
                //  status is setting in the succeeding function
                //  setStatusAfterSuccessfulProcessing.

                $metricDimensions = $this->batch->getMetricDimensions(['status' => $this->batch->getStatus()]);

                $this->trace->histogram(Batch\Metric::BATCH_ROW_PROCESS_TIME_MS, $timeTaken, $metricDimensions);

            }
            catch (BaseException $e)
            {
                // RZP Exceptions have public error code & description which can be exposed in the output file
                $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

                $error = $e->getError();

                $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE]        = $error->getPublicErrorCode();
                $entry[Batch\Header::ERROR_DESCRIPTION] = $error->getDescription();
            }
            catch (\Throwable $e)
            {
                // All non RZP exception/errors case: 1) Log critical error & 2) expose just SERVER_ERROR code in output
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

                $entry[Batch\Header::STATUS]     = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;
            }
            finally
            {
                $this->batch->incrementProcessedCount();
                $this->processFinally($entry);
            }
        }
    }

    protected function processFinally(& $entry)
    {
        return;
    }

    /**
     * This method needs to be implemented by the child classes.
     *
     * @param array $entry
     *
     */
    protected function processEntry(array & $entry)
    {
        throw new \BadMethodCallException();
    }

    /**
     * This method can be implemented by the child classes if some data
     * needs to be removed from tracing.
     *
     */
    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        return;
    }

    /**
     * Runs after all entries have been processed.
     * - Sets aggregate success and failure counts
     * - Sets status and timestamps
     *
     * Override this method if you wish to do more operations.
     *
     * @param array $entries
     */
    protected function postProcessEntries(array & $entries)
    {
        $successCount = $failureCount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] !== Batch\Status::FAILURE)
            {
                $successCount++;
            }
            else
            {
                $failureCount++;
            }
        }

        $this->batch->setSuccessCount($successCount);
        $this->batch->setFailureCount($failureCount);

        $this->pushBatchMetrics();
    }

    /**
     * pushes Batch Metrics such as Success Count,Failed Count
     * and its histogram
     */
    protected function pushBatchMetrics()
    {
        $dimensions = $this->batch->getMetricDimensions();

        $this->trace->histogram(Batch\Metric::BATCH_SUCCESS_ROWS_TOTAL,
                                $this->batch->getSuccessCount(),
                                $dimensions);

        $this->trace->histogram(Batch\Metric::BATCH_FAILED_ROWS_TOTAL,
                                $this->batch->getFailureCount(),
                                $dimensions);
    }

    /**
     * Indicates if a batch should be marked as processed even if it has partial
     * failures. This we do as partial failures in most types requires action and
     * reprocessing the same is issue.
     *
     * @return bool
     */
    protected function shouldMarkProcessedOnFailures(): bool
    {
        return true;
    }

    /**
     * Gets run at last, once batch is processed and output file is
     * created and saved.
     *
     * - Updates batch status
     * - Sends mail with aggregate data and output attached.
     * - Clean temp files.
     *
     */
    protected function postProcess()
    {
        $this->trace->info(TraceCode::BATCH_FILE_PROCESSED, $this->batch->toArrayTraceAll());

        $this->updateStatusPostProcess();

        //
        // We need to save this here only because we send a processed mail.
        // We cannot send the processed mail without saving first because
        // save can fail. In which case, we would have sent an incorrect
        // processed mail.
        //

        $this->repo->saveOrFail($this->batch);

        if ($this->batch->isProcessed() === true)
        {
            $this->sendProcessedMail();
        }

        $this->deleteLocalFiles();
    }

    /**
     * Updates the status of the batch as per the processing
     */
    public function updateStatusPostProcess()
    {
        //
        // Sets processed_at. We override this attribute whether it finally
        // processed or still in partially_processed status after multiple re-runs.
        //
        $now = Carbon::now()->getTimestamp();

        $this->batch->setProcessedAt($now);

        $this->batch->setProcessing(false);
    }

    public function setStatusAfterSuccessfulProcessing()
    {
        //
        // In some cases, we want to mark the batch as partially_processed if there is even 1 failure.
        // We also want to mark it as partially_processed if BOTH success count and failure count is 0.
        // This case occurs when the file has been processed but before processing the first row, something
        // fails or the first row fails and we are not able to update the failure count also.
        //

        //
        // Earlier, there was a total_count check here. But, now, this function is being called whenever
        // an exception is not getting thrown during the processing. If there is no error thrown, we can
        // either mark the batch as processed or partially_processed and we don't have to worry about
        // setting the batch as failed at all.
        //

        $status = Batch\Status::PROCESSED;

        if ($this->shouldMarkProcessedOnFailures() === false)
        {
            if (($this->batch->getFailureCount() > 0) or
                (($this->batch->getSuccessCount() === 0) and
                 ($this->batch->getFailureCount() === 0)))
            {
                $status = Batch\Status::PARTIALLY_PROCESSED;
            }
        }

        $this->batch->setStatus($status);
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        $this->outputFileType = $fileType;

        $entries = $this->prepareEntriesForOutputFileCreation($entries);

        $this->outputFileLocalPath = $this->createAndSetFileByExt($entries);

        try
        {
            return $this->saveOutputFile();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $this->batch->toArrayTrace());
        }
    }

    /**
     * Constructs final output associative array to be written to file:
     * - Pads null value for headers with no value in entries, so we don't get errors during xlsx creation
     * - Flatten notes fields
     *
     * @param  array  $entries
     * @return array
     */
    protected function prepareEntriesForOutputFileCreation(array $entries): array
    {
        $formatted = [];
        $headers   = $this->getOutputFileHeadings();

        $this->updateBatchHeadersIfApplicable($headers, $entries);

        foreach ($entries as $entry)
        {
            $dict = [];

            // Prepares each entry rows
            foreach ($headers as $header)
            {
                // If given header doesn't exist in entry, put a null value
                if (array_key_exists($header, $entry) === false)
                {
                    // Optional fields if not sent, shouldn't be in output file as well
                    if (in_array(
                            $header,
                            [Batch\Header::NOTES, Batch\Header::FIRST_PAYMENT_MIN_AMOUNT],
                            true) === false)
                    {
                        $dict[$header] = null;
                    }
                }
                else
                {
                    $value = $entry[$header];
                    // If the header is notes, flatten notes key & value pair at current position
                    if ($header === Batch\Header::NOTES)
                    {
                        foreach ($value as $k => $v)
                        {
                            $dict["notes[{$k}]"] = $v;
                        }
                    }
                    // If the header is products, flatten products key & value pair at current position
                    else if ($header === Batch\Header::PRODUCTS)
                    {
                        foreach ($value as $index => $productArray)
                        {
                            foreach ($productArray as $k => $v)
                            {
                                $dict["products_{$index}[{$k}]"] = $v;
                            }
                        }
                    }
                    // lowercase any value in speed column
                    else if ($header === Batch\Header::SPEED)
                    {
                        $dict[$header] = strtolower($value);
                    }
                    // Else just put the key value in dictionary
                    else
                    {
                        $dict[$header] = $value;
                    }
                }
            }

            $formatted[] = $dict;
        }
        unset($entries);

        return $formatted;
    }

    /**
     * Actually creates the output file with proper extension by calling
     * the relevant FileHandlerTrait's methods.
     *
     * @param  array  $entries
     * @return string
     * @throws LogicException
     */
    protected function createAndSetFileByExt(array $entries): string
    {
        //
        // Creation of file differs per extension, ext of output file has
        // to be same of input file.
        //
        $ext = pathinfo($this->inputFileLocalPath, PATHINFO_EXTENSION);

        $dir = $this->batch->getLocalSaveDir(self::FILE_TYPE_PREFIX_MAP[$this->outputFileType]);

        switch ($ext)
        {
            case FileStore\Format::TXT:
            case FileStore\Format::DAT:
                if ($this->ignoreHeaders === true)
                {
                    $txt = $this->generateText($entries, $this->delimiter,
                               false);

                }
                else
                {
                    $txt = $this->generateTextWithHeadings($entries, $this->delimiter,
                                           false, array_keys(current($entries)));
                }

                return $this->createTxtFile($this->batch->getFileKeyWithExt($ext), $txt, $dir);

            case FileStore\Format::CSV:
                $txt = $this->generateTextWithHeadings($entries, ',', false, array_keys(current($entries)));

                return $this->createTxtFile($this->batch->getFileKeyWithExt($ext), $txt, $dir);

            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                $fileMeta = $this->createExcelObject($entries, $dir, $this->batch->getId(), $ext, [], $this->batch->getType());

                return $fileMeta['full'];

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    protected function sendProcessedMail()
    {
        $type = studly_case($this->batch->getType());

        $mailerClass = "\\RZP\\Mail\\Batch\\$type";

        if (class_exists($mailerClass))
        {
            $mail = new $mailerClass(
                            $this->batch->toArray(),
                            $this->merchant->toArray(),
                            $this->outputFileLocalPath,
                            $this->settingsAccessor->all()->toArray());

            Mail::send($mail);
        }
    }

    protected function deleteLocalFiles()
    {
        //$this->deleteFile($this->inputFileLocalPath);
        //$this->deleteFile($this->outputFileLocalPath);
    }

    protected function deleteFile(string $filePath = null)
    {
        if (($filePath !== null) and (file_exists($filePath) === true))
        {
            $success = unlink($filePath); // nosemgrep :php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                $this->trace->critical(TraceCode::BATCH_FILE_DELETE_ERROR, ['file_path' => $filePath]);
            }
        }
    }

    /**
     * While creating the batch we parse the file and validate each entry in the file.
     * Post validation, we fill the batch entity with total_count and other metadata
     * @param  array $input
     * @return array
     */
    protected function validateInputFileEntries(array $input): array
    {
        $entries = $this->parseFileAndCleanEntries($this->inputFileLocalPath);

        $this->validateEntries($entries, $input);

        return $entries;
    }

    /**
     * Updates batch with details extracted from the input file
     *
     * @param array $entries
     * @param array $input
     */
    protected function updateBatchPostValidation(array $entries, array $input)
    {
        // Since amouunt can be in amount header or amount (in paise) header
        // use whichever is available
        $amountCol = array_column($entries, Batch\Header::AMOUNT);
        $amountInPaisaCol = array_column($entries, Batch\Header::AMOUNT_IN_PAISE);
        $amountCol = count($amountCol) > 0 ? $amountCol : $amountInPaisaCol;

        $totalAmount = array_sum($amountCol);
        $totalCount  = count($entries);

        $this->batch->setAmount($totalAmount);
        $this->batch->setTotalCount($totalCount);
    }

    /**
     * Parses input file and runs validation on the entries. Finally returns
     * the validated entries.
     *
     * @param array $entries
     * @param array $input
     */
    protected function validateEntries(array & $entries, array $input)
    {
        $this->batch->getValidator()->validateEntries($entries, $input, $this->merchant);
    }

    /**
     * Parses given file and returns the entries array
     *
     * @param string $filePath
     *
     * @return array
     * @throws LogicException
     */
    protected function parseFile(string $filePath): array
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::TXT:
            case FileStore\Format::DAT:
                //
                // We use standard separator | for txt, if needs this
                // can be made configurable. But for now it's ok.
                //
                return $this->parseTextFile($filePath, '|');

            case FileStore\Format::CSV:
                return $this->parseTextFile($filePath, ',');

            case FileStore\Format::XML:
                return $this->parseXmlFile($filePath);

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    /**
     * Parses excel sheet at given path. By default uses FileHandlerTrait's parseExcelSheets() method(existing flow).
     * But for specific batch where the flag is overridden and made true, uses new phpoffice/phpspreadsheet library.
     * We intend to move fully to this new library uses but is being done incrementally.
     * @param  string $filePath
     * @return array
     */
    protected function parseExcelSheets($filePath): array
    {
        if ($this->useSpreadSheetLibrary === true)
        {
            $this->trace->info(TraceCode::BATCH_FILE_PROCESS_USING_SPREADSHEET, $this->batch->toArrayTraceAll());

            return $this->parseExcelSheetsUsingPhpSpreadSheet($filePath, $this->getNumRowsToSkipExcelFile());
        }

        return $this->parentParseExcelSheets($filePath, $this->getStartRowExcelFiles());
    }

    public function processBatchFile(string $filePath): array
    {
        return $this->parseFileAndCleanEntries($filePath);
    }

    protected function parseFileAndCleanEntries(string $filePath): array
    {
        return $this->cleanParsedEntries($this->parseFile($filePath));
    }

    /**
     * - Trims empty rows and/or columns from xlsx/csv parsed rows.
     * - Trims additional headers of validated file, if applies.
     * @param  array $entries
     * @return array
     */
    protected function cleanParsedEntries(array $entries): array
    {
        $totalEntries = count($entries);

        // CSV: Removes first dictionary if it's the header itself
        if ((empty($entries) === false) and (array_keys($entries[0]) === array_values($entries[0])))
        {
            array_shift($entries);
        }

        // The output file that is given to merchants in the `batches/validate` api has two extra columns named `Error
        // Code` and `Error Description`. For batch create, if the merchant passes a `file_id`, the downloaded file has
        // these two columns, whose entries are removed below.
        if ($this->inputFileType === FileStore\Type::BATCH_VALIDATED)
        {
            $entries = array_map(
                        function ($entry)
                        {
                            array_forget($entry, [Batch\Header::ERROR_CODE, Batch\Header::ERROR_DESCRIPTION]);

                            return $entry;
                        },
                        $entries);
        }

        // Excel: Removes empty trailing rows
        $entries = array_filter($entries, function ($v)
        {
            return (empty(array_filter($v)) === false);
        });

        //
        // Excel: Removes empty(not all additional columns) trailing columns
        // Notes: Input file can have 0 to max 15 notes columns in the format: notes[key_1], notes[key_2]
        //        Puts formatted notes key value pair in entry for consumption by other components(in validation,
        //        processors of specific type etc)
        //
        foreach ($entries as & $entry)
        {
            $index = 0;

            foreach ($entry as $key => $value)
            {
                // Excel: Empty trailing columns comes as sequentially indexed key and null values
                if ((($key === $index++) or ($key === '')) and ($value === null))
                {
                    unset($entry[$key]);
                }
                // If key is of notes pattern pushes the key value pair in a entry's notes & unset current key
                else if (preg_match(Batch\Header::NOTES_REGEX, $key, $matches) === 1)
                {
                    unset($entry[$key]);
                    $entry[Batch\Header::NOTES][$matches[1]] = $value;
                }
                // If key is of products pattern pushes the key value pair in a entry's products & unset current key
                else if (preg_match(Batch\Header::PRODUCTS_REGEX, $key, $matches) === 1)
                {
                    unset($entry[$key]);
                    $entry[Batch\Header::PRODUCTS][$matches[1]][$matches[2]] = $value;
                }

                // Trim leading or trailing whitespace in the speed column value
                else if ($key === Batch\Header::SPEED)
                {
                    $entry[$key] = trim($value);
                }
                // Add ' if found any formulae in excel value (formulae generally starts from = and @)
                else if (
                    ($value !== null) and
                    (strlen(trim($value)) > 0) and
                    (
                        (in_array(trim($value)[0], self::EXCEL_FORMULAE_INITIATOR, true) === true) or
                        (in_array(substr(trim($value), 0, 2), self::EXCEL_FORMULAE_INITIATOR, true) === true)
                    )
                )
                {
                    if (trim($value)[0] === '"')
                    {
                        $entry[$key] = trim($value)[0] . "'" . substr(trim($value), 1);
                    }
                    else
                    {
                        $entry[$key] = "'" . trim($value);
                    }
                }
            }
        }

        $stats        = ['total_entries' => $totalEntries, 'total_cleaned_entries' => $totalEntries - count($entries)];
        $tracePayload = $this->batch->toArrayTrace([], $stats);

        $this->trace->debug(TraceCode::BATCH_PROCESS_ENTRIES_CLEANED, $tracePayload);
        return $entries;
    }

    /**
     * Saves the input file by creating a file store entity. At this point
     * doesn't associate the file store entity with batch entity.
     * That happens in callee method once file has been successfully parsed,
     * validated and batch entity has been created.
     *
     * @param File $file
     *
     * @return FileStore\Creator
     */
    protected function saveInputFile(File $file): FileStore\Creator
    {
        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArrayTrace());

        //
        // PHP's upload file get's deleted automatically once request terminates.
        // Moving this file to batch save location where UFH downloads the same
        // from S3. This helps in smooth S3 mock working.
        //

        //
        // In case of "storage" location type, getExtension would be
        // available and in case of "upload" location type,
        // getClientOriginalExtension would be available.
        //
        if ((method_exists($file, 'getExtension') === true) and
            (empty($file->getExtension()) === false))
        {
            $ext = $file->getExtension();
        }
        else
        {
            $ext = $file->getClientOriginalExtension();
        }

        $ext = strtolower($ext);

        $localDir  = $this->batch->getLocalSaveDir(Batch\Entity::INPUT_FILE_PREFIX);
        $filename  = $this->batch->getFileKeyWithExt($ext);
        $movedFile = $file->move($localDir, $filename);

        $ufh = $this->saveFile($movedFile->getPathname(), FileStore\Type::BATCH_INPUT, false);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufh->getFileInstance()->toArrayPublic());

        return $ufh;
    }

    protected function saveOutputFile(): FileStore\Creator
    {
        $associateBatch = $this->outputFileType === FileStore\Type::BATCH_OUTPUT;

        return $this->saveFile($this->outputFileLocalPath, $this->outputFileType, $associateBatch);
    }

    public function getFileIdAndSignedUrl(FileStore\Creator $ufh): array
    {
        $ufhSignedUrl = $ufh->getSignedUrl();

        return [
            self::FILE_ID    => FileStore\Entity::getSignedId($ufhSignedUrl['id']),
            self::SIGNED_URL => $ufhSignedUrl['url'],
        ];
    }

    /**
     * @param FileStore\Entity $ufh
     *
     * @return array
     */
    public function getFileIdAndSignedUrlFromFileEntity(FileStore\Entity $ufh): array
    {
        return [
            self::FILE_ID    => 'file_' . $ufh->getId(),
            self::SIGNED_URL => $ufh->getFullFilePath(),
        ];
    }

    /**
     * @param string $filePath
     * @param string $type
     * @param bool   $associateBatch - Ref: saveInputFile() for usage
     *
     * @return FileStore\Creator
     */
    protected function saveFile(string $filePath, string $type, bool $associateBatch = true): FileStore\Creator
    {
        $filePrefix = self::FILE_TYPE_PREFIX_MAP[$type];

        $name = $filePrefix . $this->batch->getFileKey();

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $ufh = new FileStore\Creator;

        if ($associateBatch === true)
        {
            $ufh->entity($this->batch);
        }

        if ($this->shouldSendToBatchService())
        {
            $ufh->addBucketConfigForBatchService(Batch\Constants::BATCH_SERVICE);
        }
        else
        {
            $ufh->addBucketConfigForBatchService(Batch\Constants::NON_MIGRATED_BATCH);
        }

        $ufh->localFilePath($filePath)
            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
            ->name($name)
            ->extension($ext)
            ->merchant($this->merchant)
            ->type($type);

        if ($this->shouldEncrypt() and
            ($type === FileStore\Type::BATCH_INPUT or $type === FileStore\Type::BATCH_VALIDATED))
        {
            $this->performEncryption($ufh);

            $this->trace->info(TraceCode::BATCH_FILE_ENCRYPTION,
                [
                    'name' => $name,
                    'type' => $type,
                ]);
        }

        return $ufh->save();
    }

    protected function performEncryption($fileHandler)
    {
        $type = Type::AES_ENCRYPTION;

        $params = [
            'mode'   => \phpseclib\Crypt\Base::MODE_CBC,
            'secret' => $this->secret,
        ];

        $encryptionHandler = new Encryption\Handler($type, $params);

        $fileToBeEncrypted = $fileHandler->getFullFilePath();

        $encryptionHandler->encryptFile($fileToBeEncrypted);
    }

    /**
     * - Downloads the input file for processing.
     * - Sets the local file path as $inputFileLocalPath.
     */
    protected function downloadAndSetInputFile()
    {
        //
        // For files of reconciliation type batches, we use a different UFH type
        // (hence S3 locations) for reasons.
        //
        $ufhTypes = ($this->batch->isReconciliationType() === true) ?
            [FileStore\Type::RECONCILIATION_BATCH_INPUT] :
            [FileStore\Type::BATCH_VALIDATED, FileStore\Type::BATCH_INPUT];

        //
        // We now fetch the file_store entity that is used for getting
        // latest entries for a batch.
        //
        // If the merchant calls the create(POST /batches) api with file upload,
        // the latest will be file of type `batch_input`.
        //
        // If the merchant calls the create(POST /batches) api with file_id produced
        // from batch validate api (POST /batches/validate), the latest will be file
        // of type `batch_validated`. This is also obvious as only the last batch_validated
        // file_store entity will be associated with this batch. The association
        // happens during batch_create sync part.
        //
        $inputFile = $this->batch
                          ->files()
                          ->whereIn(FileStore\Entity::TYPE, $ufhTypes)
                          ->latest()
                          ->first();

        $filePath = (new FileStore\Accessor)
                        ->id($inputFile->getId())
                        ->merchantId($this->batch->getMerchantId())
                        ->getFile();

        if ($inputFile->getType() === FileStore\Type::BATCH_VALIDATED)
        {
            $this->performDecryptionIfApplicable($filePath);
        }

        $this->inputFileLocalPath = $filePath;
        $this->inputFileType      = $inputFile->getType();
    }

    /**
     * Required by FileHandlerTrait for parseTextFile() method.
     *
     * @return array
     */
    public function getHeadings(): array
    {
        return Batch\Header::getHeadersForFileTypeAndBatchType($this->inputFileType, $this->batch->getType());
    }

    /**
     * {@inheritDoc}
     */
    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $headings = $this->getHeadings();
        $firstRow = str_getcsv(current($rows), $delimiter);
        $diff     = array_values(array_diff($headings, $firstRow));

        if ($diff === [Batch\Header::FEE_BEARER])
        {
            $headings = $firstRow;
        }

        //
        // In case of notes, the diff would be just 'notes' or 'speed'(for batch refunds), as the actual row will have values like notes[<key>].
        // Todo: This is because of allowing(early bad decision) optional header row in CSV.
        //
        if ((empty($diff) === true) or
            ($diff === [Batch\Header::NOTES]) or
            ($diff === [Batch\Header::SPEED]) or
            ($diff === [Batch\Header::NOTES, Batch\Header::SPEED]))
        {
            array_shift($rows);

            $headings = $firstRow;
        }
        //
        // Else 1) because notes or speed(for batch refunds) is optional column and 2) valid header is not sent, we just assume that the file
        // doesn't have notes column and so process it with headers - [notes, speed]. It will give validation error in case of
        // extra columns or other failures.
        //
        else
        {
            $headings = array_diff($headings, [Batch\Header::NOTES, Batch\Header::SPEED]);
        }

        return $headings;
    }

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix)
    {
        $msg = 'One/multiple rows have values mismatching allowed headers, please refer to guide';

        throw new BadRequestValidationFailureException($msg, Batch\Entity::FILE, compact('headings', 'values', 'ix'));
    }

    public function getOutputFileHeadings(): array
    {
        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $this->batch->getType());
    }

    protected function removeErrorColumnsFromEntries(array & $entries)
    {
        $entries = array_map(
            function ($entry)
            {
                unset($entry[Batch\Header::ERROR_CODE]);
                unset($entry[Batch\Header::ERROR_DESCRIPTION]);
                return $entry;
            },
            $entries);
    }

    /**
     * Creates output file for already processed batch.
     * NOT to be use in general.
     *
     * Ref: Batch/Core::retryBatchOutputFile
     */
    public function retryOutputFile()
    {
        $this->trace->info(TraceCode::BATCH_RETRY_OUTPUT_FILE, $this->batch->toArrayTraceAll());

        $this->validateRetryOutputFileOperationAllowed();

        $this->downloadAndSetInputFile();

        $entries = $this->parseFileAndCleanEntries($this->inputFileLocalPath);

        //
        // Gets 'receipts' from the input entries. Fetch invoices by batch id
        // and these receipts.
        //
        $receipts = array_pluck($entries, Batch\Header::INVOICE_NUMBER);

        $invoices = $this->repo->invoice->findByBatchIdAndReceipts($this->batch->getId(), $receipts);

        //
        // Makes 'receipt' the key of collection for easy access and check later
        //
        $invoices = $invoices->keyBy(Invoice\Entity::RECEIPT);

        //
        // For each entry, if there is an invoice created with the input receipt
        // have the success response appended otherwise failure response.
        //
        foreach ($entries as & $entry)
        {
            $receipt = $entry[Batch\Header::INVOICE_NUMBER];

            $invoice = $invoices->get($receipt);

            if (empty($invoice) === false)
            {
                $entry[Batch\Header::STATUS]            = $invoice->getStatus();
                $entry[Batch\Header::PAYMENT_LINK_ID]   = $invoice->getPublicId();
                $entry[Batch\Header::SHORT_URL]         = $invoice->getShortUrl();
            }
            else
            {
                $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE]        = ErrorCode::BAD_REQUEST_ERROR;
                $entry[Batch\Header::ERROR_DESCRIPTION] =
                    'Something went wrong, Request you to please contact Razorpay for assistance.';
            }
        }

        //
        // Finally create and output file with the data and set the same
        // against the batch entity.
        //
        $this->createSetOutputFileAndSave($entries);

        $this->repo->saveOrFail($this->batch);
    }

    /**
     * Above operation is only allowed for payment link type and for batches
     * not already having output file created.
     */
    protected function validateRetryOutputFileOperationAllowed()
    {
        if ($this->batch->isPaymentLinkType() === false)
        {
            throw new BadRequestValidationFailureException(
                'Operation not allowed: Batch is not of payment link type.');
        }

        if ($this->batch->outputFile() !== null)
        {
            throw new BadRequestValidationFailureException(
                'Operation not allowed: Batch already has output file created.');
        }
    }

    /**
     * Handles any exception while processing the batch, and updates the batch
     * status accordingly. Should be overridden by respective processors for any
     * special handling
     *
     * @param \Throwable $ex
     */
    protected function handleProcessingException(\Throwable $ex)
    {
        $this->trace->traceException(
            $ex,
            Trace::ERROR,
            TraceCode::BATCH_FILE_PROCESSING_ERROR,
            $this->batch->toArrayTrace());

        //
        // In case of any unhandled exceptions we set the status to failed,
        // only if it wasn't partially_processed previously and we weren't able
        // to parse the file. In all other cases the status will be set to `failed`.
        //
        // We don't set it to failed in case of partially processed because some
        // rows have already been processed and hence does not make sense to
        // mark the next attempt as failed even though this failed.
        //
        if ($this->batch->isPartiallyProcessed() === false)
        {
            $this->batch->setStatus(Batch\Status::FAILED);
        }

        //
        // Sets failure reason here because exception instance won't be available
        // in postProcess() call in finally block
        //
        if ($ex instanceof BaseException)
        {
            $failureReason = $ex->getError()->getDescription();
        }
        else
        {
            $failureReason = $ex->getMessage();
        }

        $this->batch->setFailureReason($failureReason);
    }

    /**
     * Method to configure any system limits before beginning batch processing
     * To be implemented by respective processors
     *
     * @return null
     */
    protected function increaseAllowedSystemLimits()
    {
        return;
    }

    protected function shouldEncrypt()
    {
        return $this->isEncrypted;
    }

    protected function shouldDecrypt()
    {
        return $this->isEncrypted;
    }

    protected function trimEntry(array & $entry)
    {
        foreach ($entry as $key => $row)
        {
            if ((is_array($row) === false) and (is_string($row) === true))
            {
                $entry[$key] = trim($row);
            }
        }
    }

    protected function processConvertCase(array & $entry, array $conversionMap)
    {
        foreach ($entry as $key => $row)
        {
            if ($row === null)
            {
                continue;
            }

            if (in_array($key, array_keys($conversionMap)) === true)
            {
                $entry[$key] = $this->convertCase($row, $conversionMap[$key]);
            }
        }
    }

    protected function convertCase(string $caseSensitiveString, int $type)
    {
        switch ($type)
        {
            case Batch\Constants::TO_UPPER_CASE:

                $caseSensitiveString = strtoupper($caseSensitiveString);

                break;

            case Batch\Constants::TO_LOWER_CASE:

                $caseSensitiveString = strtolower($caseSensitiveString);

                break;
        }

        return $caseSensitiveString;
    }

    public function shouldSendToBatchService(): bool
    {
        $result = false;

        if ($this->app->batchService->isCompletelyMigratedBatchType($this->batch->getType()) === true)
        {
            // not required to call razorx.
            return true;
        }

        if ($this->app->batchService->isMigratingBatchType($this->batch->getType()) === true)
        {
            //
            // Get the RazorxTreatment based on batch Type:
            // BATCH_SERVICE_<BATCH_TYPE>_MIGRATION
            // Eg: for payment_link, RazorxTreatment will be batch_service_payment_link_migration
            //
            $razorxTreatment = 'batch_service_' . $this->batch->getType() . '_migration';

            $variant = $this->getVariant($razorxTreatment);

            $result = (strtolower($variant) === 'on');
        }

        return $result;
    }

    /**
     * @param $razorxTreatment
     * @return mixed
     */
    protected function getVariant($razorxTreatment)
    {
        //
        // For reconciliation batch type we are using gateway as key
        // for other batch types the key is Merchant id
        //

        $key = $this->merchant->getId();

        if ($this->batch->getType() === Batch\Type::RECONCILIATION)
        {
            $key = $this->batch->getGateway();
        }

        $variant = $this->app->razorx->getTreatment($key,
            $razorxTreatment,
            $this->mode
        );

        return $variant;
    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        if ($this->batch->getType() === BatchType::PAYMENT_PAGE)
        {
            $headers = array_keys(current($entries));
        }

        return;
    }


    /**
     *  Checks whether validation needs to be skipped using razorx.
     *
     * @return bool
     */
    protected function shouldSkipValidateInputFile(): bool
    {
        $result = false;

        if ($this->app->batchService->isMigratingBatchType($this->batch->getType()) === true)
        {
            $variant = $this->app->razorx->getTreatment($this->merchant->getId(),
                                                        Merchant\RazorxTreatment::BATCH_SERVICE_SKIP_VALIDATION,
                                                        $this->mode
            );

            $result = (strtolower($variant) === 'on');
        }

        return $result;
    }

    protected function getStartRowExcelFiles()
    {
        return 1;
    }

    protected function getNumRowsToSkipExcelFile()
    {
        return 0;
    }

    /**
     * In some cases error code and error description are required
     * and These detail should not be reset .
     *
     * @return bool
     */
    protected function resetErrorOnSuccess(): bool
    {
        return true;
    }

    public function addSettingsIfRequired(& $input)
    {
        if($this->batch->getType() === BatchType::MERCHANT_UPLOAD_MIQ)
        {
            $admin = $this->app['basicauth']->getAdmin();

            $input[Batch\Entity::CONFIG][Merchant\Entity::ORG_ID] = $admin->getOrgId();

            return;
        }
        return;
    }
}
