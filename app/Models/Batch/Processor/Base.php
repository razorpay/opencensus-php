<?php

namespace RZP\Models\Batch\Processor;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Models\Base as BaseModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Base extends BaseModel\Core
{
    use FileHandlerTrait;

    /**
     * Lock wait timeout for batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 2500;

    /**
     * XLSX mime type
     */
    const XLSX_MIME_TYPE     = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

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
    protected $params;

    /**
     * Holds local file path of input and output file respectively.
     * They are re-used in the flow.
     * E.g.
     * - sending mails with attachment,
     * - unlinking post processing etc..
     */
    protected $inputFileLocalPath;
    protected $outputFileLocalPath;

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

    public function process()
    {
        $this->batch->getValidator()->validateNotProcessedAlready();

        $this->batch->incrementAttempts();

        $this->downloadAndSetInputFile();

        $entries = $this->parseExcelSheets($this->inputFileLocalPath);

        $this->mutex->acquireAndRelease(
            $this->batch->getId(),
            function () use ($entries)
            {
                $this->processEntries($entries);

                $this->postProcessEntries($entries);

                $this->createAndSetOutputFile($entries);

                $this->saveOutputFile();

                $this->repo->saveOrFail($this->batch);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(TraceCode::BATCH_FILE_PROCESSED, $this->batch->toArrayPublic());

        $this->postProcess();
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
            try
            {
                $this->trace->debug(
                                TraceCode::BATCH_PROCESSING_ENTRY,
                                [
                                    'batch' => $this->batch->toArrayDebug(),
                                    'entry' => $entry,
                                ]);

                $this->processEntry($entry);

                // Set errors as null

                $entry[Batch\Header::ERROR_CODE]        = null;
                $entry[Batch\Header::ERROR_DESCRIPTION] = null;
            }
            catch (Exception\BaseException $e)
            {
                // All RZP Exceptions have public error code and public error
                // description which can be exposed in the output file.

                $this->trace->traceException(
                                $e,
                                null,
                                TraceCode::BATCH_PROCESSING_ERROR,
                                $this->batch->toArrayDebug());

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
                                $this->batch->toArrayDebug());

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
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
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

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $this->batch->setProcessedAt($now);

        $this->batch->setStatus(Batch\Status::PROCESSED);
    }

    /**
     * Out of db transaction: Gets run at last, once batch is processed and
     * output file is created and saved.
     *
     * - Sends mail with aggregate data and output attached.
     * - Clean temp files.
     *
     */
    protected function postProcess()
    {
        if ($this->batch->isProcessed() === true)
        {
            $this->sendProcessedMail();
        }

        $this->deleteFile($this->outputFileLocalPath);

        $this->deleteFile($this->inputFileLocalPath);
    }

    /**
     * - Method to generate the output file (excel always) from the processed
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
        // Constructs final excel input using updated $entries set. This things
        // is used to create output excel file.
        //

        $excelInput = [];

        foreach ($entries as $entry)
        {
            $dict = array_combine($headers, array_fill(0, $fieldsCount, null));

            foreach ($entry as $key => $value)
            {
                $dict[$key] = $value;
            }

            $excelInput[] = $dict;
        }

        $fileMeta = $this->createExcelObject(
                                $excelInput,
                                $this->batch->getId(),
                                [],
                                $this->batch->getType()
                            )
                         ->store(
                                FileStore\Format::XLSX,
                                $this->batch->getLocalSaveDir(),
                                true
                            );

        $this->outputFileLocalPath = $fileMeta['full'];

        unset($entries);
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
        }
    }

    public function saveInputFile(UploadedFile $file): \SplFileInfo
    {
        //
        // PHP's upload file get's deleted automatically once request terminates.
        // Moving this file to batch save location where UFH downloads the same
        // from S3. This helps in smooth S3 mock working.
        //

        $file = $file->move(
                        $this->batch->getLocalSaveDir(),
                        $this->batch->getFileKeyWithExt());

        $ufh = $this->saveFile($file->getPathname(), FileStore\Type::BATCH_INPUT);

        $this->batch->setUploadFileUrl($ufh->getUrl());

        $ufhFile = $ufh->getFileInstance();

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufhFile->toArrayPublic());

        return $file;
    }

    public function saveOutputFile()
    {
        $ufh = $this->saveFile(
                        $this->outputFileLocalPath,
                        FileStore\Type::BATCH_OUTPUT);

        $this->batch->setDownloadFileUrl($ufh->getUrl());
    }

    /**
     * @param string $filePath
     * @param string $type
     *
     * @return FileStore\Creator
     *
     * @throws Exception\LogicException
     */
    protected function saveFile(string $filePath, string $type): FileStore\Creator
    {
        $name = $this->batch->getFilePrefix() . $this->batch->getFileKey();

        return (new FileStore\Creator)
                    ->localFilePath($filePath)
                    ->mime(self::XLSX_MIME_TYPE)
                    ->name($name)
                    ->extension(FileStore\Format::XLSX)
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

            $saveAs = $this->batch->getLocalSavePath(Batch\Status::CREATED);

            $filePath = $this->getFileFromAws($awsKey, $saveAs);
        }

        $this->inputFileLocalPath = $filePath;
    }
}
