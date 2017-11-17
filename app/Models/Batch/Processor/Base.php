<?php

namespace RZP\Models\Batch\Processor;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Exception\BadRequestValidationFailureException;

class Base extends BaseModel\Core
{
    use FileHandlerTrait;

    /**
     * Lock wait timeout for batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 2500;

    /**
     * The MUTEX instance
     */
    protected $mutex;

    /**
     * The batch entity which is being processed
     * @var Batch\Entity
     */
    protected $batch;

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
     * Holds local file path of input and output file respectively.
     * They are re-used in the flow.
     * E.g.
     * - sending mails with attachment,
     * - unlinking post processing etc..
     */
    protected $inputFileLocalPath = "";
    protected $outputFileLocalPath = "";

    /**
     * Static method returns instance of processor based on type of batch
     * passed as arg.
     *
     * @param Batch\Entity $batch
     *
     * @return Base
     */
    public static function get(Batch\Entity $batch)
    {
        $processor = __NAMESPACE__ . '\\' . studly_case($batch->getType());

        return new $processor($batch);
    }

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->batch = $batch;

        $this->merchant = $batch->merchant;
    }

    public function setParams(array $params = null)
    {
        // To maintain backward compatibility with old queue jobs.
        // Old queue job will have $params as null in Job\Batch class.

        $this->params = $params ?: [];

        return $this;
    }

    /**
    * Does following inside transaction:
    * - Uploads file to s3 and gets FileStore\Entity created
    * - Validates the file
    * - Updates batch entity with aggregate details of file (if applicable)
    * - Saves batch entity
    *
    * @param array $input
    */
    public function storeInputFileAndSaveBatch(array $input)
    {
        $this->repo->transaction(function () use ($input)
        {
            $inputFile = $input[Batch\Entity::FILE];

            $file = $this->saveInputFile($inputFile);

            $this->validateInputFileAndUpdateBatch($file->getPathname(), $input);

            $this->repo->saveOrFail($this->batch);
        });
    }

    /**
     * Checks if the batch can be processed, if yes sets the processing flag
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
            $this->trace->info(TraceCode::BATCH_FILE_PROCESSING, $this->batch->toArray());

            $this->performPreProcessingActions();

            $this->parseAndProcessBatchEntries();
        }
        catch (\Throwable $ex)
        {
            $this->handleBatchProcessingException($ex);
        }
        finally
        {
            $this->postProcess();

            $this->trace->info(TraceCode::BATCH_FILE_PROCESSED, $this->batch->toArray());
        }
    }

    protected function performPreProcessingActions()
    {
        $this->increaseAllowedSystemLimits();

        $this->batch->incrementAttempts();

        $this->downloadAndSetInputFile();
    }

    protected function parseAndProcessBatchEntries()
    {
        $entries = $this->parseFile($this->inputFileLocalPath);

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
        foreach ($entries as & $entry)
        {
            $tracePayload = [
                Batch\Entity::ID          => $this->batch->getId(),
                Batch\Entity::MERCHANT_ID => $this->batch->getMerchantId(),
                'entry'                   => $entry,
            ];

            try
            {
                $this->trace->debug(TraceCode::BATCH_PROCESSING_ENTRY, $tracePayload);

                $this->processEntry($entry);

                // Set errors as null

                $entry[Batch\Header::ERROR_CODE]        = null;
                $entry[Batch\Header::ERROR_DESCRIPTION] = null;
            }
            catch (BaseException $e)
            {
                // All RZP Exceptions have public error code and public error
                // description which can be exposed in the output file.

                $this->trace->traceException(
                                $e,
                                null,
                                TraceCode::BATCH_PROCESSING_ERROR,
                                $tracePayload);

                $error = $e->getError();

                $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE]        = $error->getPublicErrorCode();
                $entry[Batch\Header::ERROR_DESCRIPTION] = $error->getDescription();
            }
            catch (\Throwable $e)
            {
                // All non RZP exception/errors case:
                // - Log critical error
                // - Just expose error code SERVER_ERROR in output file.

                $this->trace->traceException(
                                $e,
                                Trace::CRITICAL,
                                TraceCode::BATCH_PROCESSING_ERROR,
                                $tracePayload);

                $entry[Batch\Header::STATUS]     = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;
            }
        }
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
        $this->updateBatchStatusPostProcess();

        $this->repo->saveOrFail($this->batch);

        if ($this->batch->isProcessed() === true)
        {
            $this->sendProcessedMail();
        }

        $this->deleteFile($this->outputFileLocalPath);

        $this->deleteFile($this->inputFileLocalPath);
    }

    /**
     * Updates the status of the batch as per the processing
     */
    protected function updateBatchStatusPostProcess()
    {
        //
        // Sets processed_at. We override this attribute whether it finally
        // processed or still in partially_processed status after multiple re-runs.
        //
        $now = Carbon::now()->getTimestamp();

        $this->batch->setProcessedAt($now);

        //
        // We set the batch status to processed unless it failed because of some
        // unhandled error in the current run.
        //
        $status = ($this->batch->isFailed() === true) ?
                    Batch\Status::FAILED :
                    Batch\Status::PROCESSED;

        //
        // If we were able to successfully parse the file the total_count will be
        // greater than 0. We only want to mark the file as processed / partially_processed
        // in such a case
        //
        if ($this->batch->getTotalCount() > 0)
        {
            //
            // But if we were able to process the file and there were failures, we
            // mark it as partially_processed or processed depending on the type of
            // the file.
            //
            if (($this->batch->getFailureCount() > 0) or
                (($this->batch->getSuccessCount() === 0) and
                ($this->batch->getFailureCount() === 0)))
            {
                $status = ($this->shouldMarkProcessedOnFailures() === true) ?
                            Batch\Status::PROCESSED :
                            Batch\Status::PARTIALLY_PROCESSED;
            }
        }

        //
        // If in the current run the batch has been processed, we reset the failure
        // reason to maintain consistency
        //
        if ($status === Batch\Status::PROCESSED)
        {
            $this->batch->unsetFailureReason();
        }

        $this->batch->setStatus($status);

        $this->batch->setProcessing(false);
    }

    protected function createSetOutputFileAndSave(array & $entries)
    {
        try
        {
            $this->createAndSetOutputFile($entries);

            $this->saveOutputFile();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::BATCH_PROCESSING_ERROR,
                            $this->batch->toArrayPublic());
        }
    }

    /**
     * - Method to generate the output file (excel/text) from the processed
     *   entries.
     *
     * @param array $entries
     */
    protected function createAndSetOutputFile(array & $entries)
    {
        $type = $this->batch->getType();

        $headers = Batch\Header::PER_TYPE[$type][Batch\Header::OUTPUT];

        $fieldsCount = count($headers);

        //
        // Constructs final input using updated $entries set. This things
        // is used to create output file. Below we fill in the empty headers
        // with null so we don't get errors during creation of files.
        //
        $cleanedEntries = [];

        foreach ($entries as $entry)
        {
            $dict = array_combine($headers, array_fill(0, $fieldsCount, null));

            foreach ($entry as $key => $value)
            {
                $dict[$key] = $value;
            }

            $cleanedEntries[] = $dict;
        }

        $this->createAndSetOutputFileByExt($cleanedEntries);

        unset($entries, $cleanedEntries);
    }

    /**
     * Actually creates the output file with proper extension by calling
     * the relevant FileHandlerTrait's methods.
     *
     * @param array $entries
     * @throws LogicException
     */
    protected function createAndSetOutputFileByExt(array $entries)
    {
        //
        // Creation of file differs per extension, ext of output file has
        // to be same of input file.
        //
        $ext = pathinfo($this->inputFileLocalPath, PATHINFO_EXTENSION);

        $dir = $this->batch->getLocalSaveDir(Batch\Entity::OUTPUT_FILE_PREFIX);

        switch ($ext)
        {
            case FileStore\Format::TXT:
                $txt = $this->generateText($entries, '|');
                $this->outputFileLocalPath = $this->createTxtFile($this->getFileName($ext), $txt, $dir);
                return;

            case FileStore\Format::CSV:
                $txt = $this->generateText($entries, ',');
                $this->outputFileLocalPath = $this->createTxtFile($this->getFileName($ext), $txt, $dir);
                return;

            case FileStore\Format::XLSX:
                $fileMeta = $this->createExcelObject(
                                    $entries,
                                    $this->batch->getId(),
                                    [],
                                    $this->batch->getType()
                                 )
                                 ->store($ext, $dir, true);
                $this->outputFileLocalPath = $fileMeta['full'];
                return;

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    protected function getFileName(string $ext = null): string
    {
        if (empty($ext) === true)
        {
            return $this->batch->getFileKey();
        }

        return $this->batch->getFileKeyWithExt($ext);
    }

    protected function sendProcessedMail()
    {
        $type = studly_case($this->batch->getType());

        $mailerClass = "\\RZP\\Mail\\Batch\\$type";

        $mail = new $mailerClass(
                        $this->batch->toArray(),
                        $this->merchant->toArray(),
                        $this->outputFileLocalPath);

        Mail::send($mail);
    }

    public function deleteFile(string $filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath);

            if ($success === false)
            {
                $this->trace->critical(TraceCode::BATCH_FILE_DELETE_ERROR, [
                    'file_path' => $filePath
                ]);
            }
        }
    }

    /**
     * While creating the batch we parse the file and validate each entry in the file.
     * Post validation, we fill the batch entity with total_count and other metadata
     *
     * @param  string $filePath
     * @param  array  $input
     */
    protected function validateInputFileAndUpdateBatch(string $filePath, array $input)
    {
        $entries = $this->parseFile($filePath);

        $this->validateEntries($entries, $input);

        $this->fillBatchEntityWithInputFileDetails($entries);
    }

    /**
     * Fills Batch entity with details extracted from the input file.
     * Eg.
     * - Total row count
     * - Aggregate sum of amount field
     *
     * @param array $entries
     */
    protected function fillBatchEntityWithInputFileDetails(array $entries)
    {
        $totalAmount = array_sum(array_column($entries, Batch\Header::AMOUNT));
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
    protected function validateEntries(array $entries, array $input)
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
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::TXT:
                //
                // We use standard separator | for txt, if needs this
                // can be made configurable. But for now it's ok.
                //
                return $this->parseTextFile($filePath, '|');

            case FileStore\Format::CSV:
                return $this->parseTextFile($filePath, ',');

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    protected function saveInputFile(File $file): File
    {
        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArray());

        //
        // PHP's upload file get's deleted automatically once request terminates.
        // Moving this file to batch save location where UFH downloads the same
        // from S3. This helps in smooth S3 mock working.
        //

        $ext = $file->getClientOriginalExtension();

        $file = $file->move(
                    $this->batch->getLocalSaveDir(Batch\Entity::INPUT_FILE_PREFIX),
                    $this->getFileName($ext));

        $ufh = $this->saveFile($file->getPathname(), FileStore\Type::BATCH_INPUT);

        $this->batch->setUploadFileUrl($ufh->getUrl());

        $ufhFile = $ufh->getFileInstance();

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufhFile->toArrayPublic());

        return $file;
    }

    protected function saveOutputFile()
    {
        $ufh = $this->saveFile($this->outputFileLocalPath, FileStore\Type::BATCH_OUTPUT);

        $this->batch->setDownloadFileUrl($ufh->getUrl());
    }

    /**
     * @param string $filePath
     * @param string $type
     *
     * @return FileStore\Creator
     *
     * @throws LogicException
     */
    protected function saveFile(string $filePath, string $type): FileStore\Creator
    {
        $batchFilePrefix = ($type === FileStore\Type::BATCH_INPUT) ?
                                Batch\Entity::INPUT_FILE_PREFIX :
                                Batch\Entity::OUTPUT_FILE_PREFIX;

        $name = $batchFilePrefix . $this->batch->getFileKey();

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        return (new FileStore\Creator)
                    ->localFilePath($filePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
                    ->name($name)
                    ->extension($ext)
                    ->entity($this->batch)
                    ->merchant($this->merchant)
                    ->type($type)
                    ->save();
    }

    /**
     * - Downloads the input file for processing.
     * - Sets the local file path as $inputFileLocalPath.
     */
    protected function downloadAndSetInputFile()
    {
        $inputFile = $this->batch->inputFile();

        //
        // Handles backward compatibility:
        // New entries will have reference to UFH but older ones unless migrated
        // will not have reference. So using the old way (else block) of forming
        // S3 object key and then fetches the same.
        //
        if ($inputFile !== null)
        {
            $filePath = (new FileStore\Accessor)
                            ->id($inputFile->getId())
                            ->merchantId($this->batch->getMerchantId())
                            ->getFile();
        }
        else
        {
            $awsKey = $this->batch->getFilePrefix(Batch\Status::CREATED) .
                            $this->batch->getFileKeyWithExt();

            $saveAs = $this->batch->getLocalSavePath(Batch\Entity::INPUT_FILE_PREFIX);

            $filePath = $this->getFileFromAws($awsKey, $saveAs);
        }

        $this->inputFileLocalPath = $filePath;
    }

    /**
     * Required by FileHandlerTrait for parseTextFile() method.
     *
     * @return array
     */
    public function getHeadings()
    {
        return $this->batch->getHeaders();
    }

    /**
     * Creates output file for already processed batch.
     * NOT to be use in general.
     *
     * Ref: Batch/Core::retryBatchOutputFile
     */
    public function retryBatchOutputFile()
    {
        $this->trace->info(TraceCode::BATCH_RETRY_OUTPUT_FILE, $this->batch->toArrayPublic());

        $this->validateRetryBatchOutputFileOperationAllowed();

        $this->downloadAndSetInputFile();

        $entries = $this->parseFile($this->inputFileLocalPath);

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
                $entry[Batch\Header::ERROR_DESCRIPTION] = 'Something went wrong, Request you to please contact Razorpay for assistance.';
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
    protected function validateRetryBatchOutputFileOperationAllowed()
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
     * status accordingly. Should be overrideen by respective processors for any
     * special handling
     *
     * @param \Throwable $ex
     */
    protected function handleBatchProcessingException(\Throwable $ex)
    {
        $this->trace->traceException(
            $ex,
            Trace::ERROR,
            TraceCode::BATCH_FILE_PROCESSING_ERROR,
            [
                Batch\Entity::ID   => $this->batch->getId(),
                Batch\Entity::TYPE => $this->batch->getType(),
            ]);

        //
        // In case of any unhandled exceptions we set the status to failed,
        // only if it wasn't partially_processed previously and we weren't able
        // to parse the file. In all other cases the old status will continue.
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
}
