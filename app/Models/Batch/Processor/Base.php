<?php

namespace RZP\Models\Batch\Processor;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Batch\Constants;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Exception\BadRequestValidationFailureException;

class Base extends BaseModel\Core
{
    use FileHandlerTrait;

    const FILE_ID           = 'file_id';

    const SIGNED_URL        = 'signed_url';

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
     * Map for the file type of batch entity and the
     * corresponding path where they should be stored.
     */
    const FILE_TYPE_PREFIX_MAP = [

        FileStore\Type::BATCH_INPUT     => Batch\Entity::INPUT_FILE_PREFIX,

        FileStore\Type::BATCH_VALIDATED     => Batch\Entity::VALIDATED_FILE_PREFIX,

        FileStore\Type::BATCH_OUTPUT    => Batch\Entity::OUTPUT_FILE_PREFIX,
    ];

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
     * @var Settings\Accessor
     */
    protected $settingsAccessor;

    /**
     * Holds local file path of input, output and generated file respectively.
     * They are re-used in the flow.
     * E.g.
     * - sending mails with attachment,
     * - unlinking post processing etc..
     */
    protected $inputFileLocalPath = "";
    protected $outputFileLocalPath = "";
    protected $validatedFileLocalPath = "";
    protected $generatedFileLocalPath = "";

    /**
     * Holds path where generated file must be saved
     */
    protected $generatedFileDirectory = "";

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct();

        $this->mutex            = $this->app['api.mutex'];
        $this->batch            = $batch;
        $this->merchant         = $batch->merchant;
        $this->settingsAccessor = Settings\Accessor::for($this->batch, Settings\Module::BATCH);
    }

    public function setParams(array $params = null)
    {
        // To maintain backward compatibility with old queue jobs.
        // Old queue job will have $params as null in Job\Batch class.

        $this->params = $params ?: [];

        return $this;
    }

    /**
    * Stores input file to file store, does parsing and basic validation and
    * then saves the batch with its input configurations.
    *
    * @param array $input
    */
    public function storeInputFileAndSaveBatchWithSettings(array $input)
    {
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

        /**
         * For file upload, we throw an error in Batch/Validator::validateEntries.
         * This attribute is used in this validator. Its not stored though.
         */
        if (isset($input[Batch\Entity::FILE]) === true)
        {
            $this->batch->setCreatedByFileUpload(true);
        }

        // For new flow, the file_store entity referenced by
        // `file_id` in input gets associated with this batch
        $ufhFile = $this->saveInputFileAndValidateEntries($input);

        $ufhFile->entity()->associate($this->batch);

        $this->repo->transaction(function () use ($ufhFile, $input)
        {
            $this->repo->saveOrFail($ufhFile);

            $this->repo->saveOrFail($this->batch);

            $this->saveSettings($input);
        });
    }

    public function storeAndValidateInputFile(array $input): array
    {
        $validatedEntries = $this->fetchValidatedEntriesFromInputFile($input);

        $response = $this->getValidatedEntriesStatsAndPreview($validatedEntries);

        $ufh = $this->createValidatedFileAndSave($validatedEntries);

        $response += $this->getFileIdAndSignedUrl($ufh);

        $this->deleteFile($this->validatedFileLocalPath);

        $this->deleteFile($this->inputFileLocalPath);

        return $response;
    }

    public function saveInputFileAndValidateEntries(array $input)
    {
        // Here $ufhFile is the input file_store instance upload by merchant.
        // $ufhFile has no entity associated with it and has type = `batch_input`
        $ufhFile = $this->getInputFile($input);

        // Update input file path
        $this->inputFileLocalPath = $ufhFile->getFullFilePath();

        // $entries here might have extra error_code and error_description headers
        $entries = $this->validateInputFileEntries($this->inputFileLocalPath, $input);

        $this->updateBatchPostValidation($entries, $input);

        return $ufhFile;
    }

    public function fetchValidatedEntriesFromInputFile(array $input): array
    {
        // Here $ufhFile is the input file_store instance upload by merchant.
        // $ufhFile has no entity associated with it and has type = `batch_input`
        $ufhFile = $this->getInputFile($input);

        // Update input file path
        $this->inputFileLocalPath = $ufhFile->getFullFilePath();

        // $entries here has the extra error_code and error_description headers
        return $this->validateInputFileEntries($this->inputFileLocalPath, $input);
    }

    public function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $correctEntries = array_filter($entries, function($entry)
        {
            return (isset($entry[Batch\Header::ERROR_CODE]) === false);
        });

        $response = [
            Constants::PROCESSABLE_COUNT     => count($correctEntries),
            Constants::ERROR_COUNT           => count($entries) - count($correctEntries),
            Constants::PARSED_ENTRIES        => array_slice($correctEntries, 0, self::MAX_PARSED_ROWS),
        ];

        return $response;
    }

    /**
     * - If file_store entity is is passed in input, fetches file
     * from s3 and saves it in filestore/batch/upload folder in local.

     * - If file is uploaded (contains file object in $input)
     * a file_store entity is created with batch_input as type
     * which uploads the file in filestore/batch/upload
     * folder in s3 too. The original file is saved in
     * filestore/batch/upload folder in local.
     *
     * @param array $input
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

            return $accessor->get();
        }

        $inputFile = $input[Batch\Entity::FILE];

        // Saves the merchant uploaded input file with file type
        // as `batch_input` and no entity associated with it
        $ufh = $this->saveInputFile($inputFile);

        $ufhFile = $ufh->getFileInstance();

        return $ufhFile;
    }

    protected function saveSettings(array $input)
    {
        if (isset($input[Batch\Entity::CONFIG]) === true)
        {
            $config = $input[Batch\Entity::CONFIG];

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
            $this->trace->info(TraceCode::BATCH_FILE_PROCESSING, $this->batch->toArray());

            $this->performPreProcessingActions();

            $this->parseAndProcessEntries();
        }
        catch (\Throwable $ex)
        {
            $this->handleProcessingException($ex);
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

    protected function parseAndProcessEntries()
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

        $this->deleteFile($this->outputFileLocalPath);

        $this->deleteFile($this->inputFileLocalPath);
    }

    /**
     * Updates the status of the batch as per the processing
     */
    protected function updateStatusPostProcess()
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

    public function createSetOutputFileAndSave(array & $entries)
    {
        $type = $this->batch->getType();

        $headers = Batch\Header::getOutputHeadersForType($type);

        $this->generatedFileDirectory = $this->batch->getLocalSaveDir(Batch\Entity::OUTPUT_FILE_PREFIX);

        // Creates an output file in `batch/download` folder with
        // same name as `$this->batch->getId . <desired_extension>`.
        // <desired_extension> is XLSX by default.
        // The output file path is saved in $this->outputFileLocalPath.
        $this->createAndSetGeneratedFile($entries, $headers);

        $this->outputFileLocalPath = $this->generatedFileLocalPath;

        try
        {
            return $this->saveOutputFile();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::BATCH_PROCESSING_ERROR,
                            $this->batch->toArrayPublic());
        }

        return null;
    }

    public function createValidatedFileAndSave(array & $entries)
    {
        $type = $this->batch->getType();

        $headers = Batch\Header::getValidatedHeadersForType($type);

        $this->generatedFileDirectory = $this->batch->getLocalSaveDir(Batch\Entity::VALIDATED_FILE_PREFIX);

        // Creates an error file in `batch/error` folder with
        // same name as `$this->batch->getId . <desired_extension>`.
        // <desired_extension> is XLSX by default.
        // The error file path is saved in $this->errorFileLocalPath.
        $this->createAndSetGeneratedFile($entries, $headers);

        $this->validatedFileLocalPath = $this->generatedFileLocalPath;

        try
        {
            return $this->saveValidatedFile();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::BATCH_PROCESSING_ERROR,
                $this->batch->toArrayPublic());
        }

        return null;
    }

    /**
     * - Method to generate the output file (excel/text) from the processed
     *   entries.
     *
     * @param array $entries
     * @param array $headers
     */
    protected function createAndSetGeneratedFile(array & $entries, array $headers)
    {
        //
        // Constructs final input using updated $entries set. This things
        // is used to create output file. Below we fill in the empty headers
        // with null so we don't get errors during creation of files.
        //
        $cleanedEntries = [];

        $fieldsCount = count($headers);

        foreach ($entries as $entry)
        {
            $dict = array_combine($headers, array_fill(0, $fieldsCount, null));

            foreach ($entry as $key => $value)
            {
                $dict[$key] = $value;
            }

            $cleanedEntries[] = $dict;
        }

        $this->createAndSetFileByExt($cleanedEntries);

        unset($entries, $cleanedEntries);
    }

    /**
     * Actually creates the output file with proper extension by calling
     * the relevant FileHandlerTrait's methods.
     *
     * @param array $entries
     * @throws LogicException
     */
    protected function createAndSetFileByExt(array $entries)
    {
        //
        // Creation of file differs per extension, ext of output file has
        // to be same of input file.
        //
        $ext = pathinfo($this->inputFileLocalPath, PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::TXT:
                $txt = $this->generateText($entries, '|');
                $this->generatedFileLocalPath = $this->createTxtFile(
                                                        $this->batch->getFileKeyWithExt($ext),
                                                        $txt,
                                                        $this->generatedFileDirectory);
                return;

            case FileStore\Format::CSV:
                $txt = $this->generateText($entries, ',');
                $this->generatedFileLocalPath = $this->createTxtFile(
                                                        $this->batch->getFileKeyWithExt($ext),
                                                        $txt,
                                                        $this->generatedFileDirectory);
                return;

            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                $fileMeta = $this->createExcelObject(
                                    $entries,
                                    $this->batch->getId(),
                                    [],
                                    $this->batch->getType()
                                    )
                                 ->store($ext, $this->generatedFileDirectory, true);
            $this->generatedFileLocalPath = $fileMeta['full'];
                return;

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
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
     * @param  array $input
     * @return array
     */
    protected function validateInputFileEntries(string $filePath, array $input): array
    {
        $entries = $this->parseFile($filePath);

        // This cleanup is required because when we validate
        // the entries, we check the headers in the entries
        $this->removeErrorColumnsFromEntries($entries);

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
        $totalAmount = array_sum(array_column($entries, Batch\Header::AMOUNT));
        $totalCount  = count($entries);

        $this->batch->setAmount($totalAmount);
        $this->batch->setTotalCount($totalCount);
    }

    /**
     * The output file that is given to merchants in the `batches/validate` api
     * has two extra columns named `Error Code` and `Error Description`.
     * For batch create, if the merchant passes a `file_id`, the downloaded file
     * has these two columns, whose entries are removed in this method.
     *
     * @param array $entries
     */
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
        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArray());

        //
        // PHP's upload file get's deleted automatically once request terminates.
        // Moving this file to batch save location where UFH downloads the same
        // from S3. This helps in smooth S3 mock working.
        //

        $ext = $file->getClientOriginalExtension();

        $movedFile = $file->move(
                        $this->batch->getLocalSaveDir(Batch\Entity::INPUT_FILE_PREFIX),
                        $this->batch->getFileKeyWithExt($ext));

        $ufh = $this->saveFile(
                        $movedFile->getPathname(),
                        FileStore\Type::BATCH_INPUT,
                        false);

        $this->trace->info(
            TraceCode::BATCH_UPLOAD_FILE,
            $ufh->getFileInstance()->toArrayPublic());

        return $ufh;
    }

    protected function saveOutputFile(): FileStore\Creator
    {
        return $this->saveFile(
                        $this->outputFileLocalPath,
                        FileStore\Type::BATCH_OUTPUT,
                        true);
    }

    protected function saveValidatedFile(): FileStore\Creator
    {
        return $this->saveFile(
                        $this->validatedFileLocalPath,
                        FileStore\Type::BATCH_VALIDATED,
                        false);
    }

    public function getFileIdAndSignedUrl(FileStore\Creator $ufh): array
    {
        $ufhSignedUrl = $ufh->getSignedUrl();

        return [
            self::FILE_ID       => FileStore\Entity::getSignedId($ufhSignedUrl['id']),
            self::SIGNED_URL    => $ufhSignedUrl['url'],
        ];
    }

    /**
     * @param string $filePath
     * @param string $type
     * @param bool   $associateBatch - Ref: saveInputFile() for usage
     *
     * @return FileStore\Creator
     *
     * @throws LogicException
     */
    protected function saveFile(
        string $filePath,
        string $type,
        bool $associateBatch = true): FileStore\Creator
    {
        $filePrefix = self::FILE_TYPE_PREFIX_MAP[$type];

        $name = $filePrefix . $this->batch->getFileKey();

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        $ufh = new FileStore\Creator;

        if ($associateBatch === true)
        {
            $ufh->entity($this->batch);
        }

        return $ufh->localFilePath($filePath)
                   ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
                   ->name($name)
                   ->extension($ext)
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
        //
        // For files of reconciliation type batches, we use a different UFH type
        // (hence S3 locations) for reasons.
        //
        $ufhTypes = ($this->batch->isReconciliationType() === true) ?
            [FileStore\Type::RECONCILIATION_BATCH_INPUT] :
            [FileStore\Type::BATCH_VALIDATED, FileStore\Type::BATCH_INPUT];

        /**
         * We now fetch the file_store entity that is used for getting
         * latest entries for a batch.
         *
         * If the merchant calls the create(POST /batches) api with file upload,
         * the latest will be file of type `batch_input`.
         *
         * If the merchant calls the create(POST /batches) api with file_id produced
         * from batch validate api (POST /batches/validate), the latest will be file
         * of type `batch_validated`. This is also obvious as only the last batch_validated
         * file_store entity will be associated with this batch. The association
         * happens during batch_create sync part.
         */
        $inputFile = $this->batch
                          ->files()
                          ->whereIn(FileStore\Entity::TYPE, $ufhTypes)
                          ->latest()
                          ->first();

        $filePath = (new FileStore\Accessor)
                        ->id($inputFile->getId())
                        ->merchantId($this->batch->getMerchantId())
                        ->getFile();

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
    public function retryOutputFile()
    {
        $this->trace->info(TraceCode::BATCH_RETRY_OUTPUT_FILE, $this->batch->toArrayPublic());

        $this->validateRetryOutputFileOperationAllowed();

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
     * status accordingly. Should be overrideen by respective processors for any
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
