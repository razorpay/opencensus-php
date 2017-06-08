<?php

namespace RZP\Models\Batch;

use Carbon\Carbon;
use Config;
use Mail;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use RZP\Models\FileStore;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Processor extends Base\Core
{
    use FileHandlerTrait;

    const XLSX_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    protected $mutex;

    /**
     * @var Entity
     */
    protected $batch;

    /**
     * Holds local file path of input and output file respectively.
     * They are re-used in the flow. E.g. sending mails with attachment,
     * un-linking post processing etc.
     */
    protected $inputFileLocalPath;
    protected $outputFileLocalPath;

    const MUTEX_LOCK_TIMEOUT = 2500;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function saveInputFile(Entity $batch, UploadedFile $file): \SplFileInfo
    {
        //
        // PHP's upload file get's deleted automatically once request terminates.
        // Moving this file to batch save location where UFH downloads the same
        // from S3. This helps in smooth S3 mock working.
        //

        $movedFile = $file->move($batch->getLocalSaveDir(), $batch->getFileKeyWithExt());

        $filePath = $movedFile->getPathname();

        $ufhFile = $this->saveFile($batch, $filePath, FileStore\Type::BATCH_INPUT);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufhFile->toArrayPublic());

        $this->deleteFile($filePath);

        return $movedFile;
    }

    public function saveOutputFile(Entity $batch, string $filePath)
    {
        $this->saveFile($batch, $filePath, FileStore\Type::BATCH_OUTPUT);
    }

    /**
     * @param Entity $batch
     * @param string $filePath
     * @param string $type
     *
     * @return FileStore\Entity
     * @throws Exception\LogicException
     */
    protected function saveFile(
        Entity $batch,
        string $filePath,
        string $type)
    {
        $name = $batch->getFilePrefix() . $batch->getFileKey();

        return (new FileStore\Creator)
                    ->localFilePath($filePath)
                    ->mime(self::XLSX_MIME_TYPE)
                    ->name($name)
                    ->extension(FileStore\Format::XLSX)
                    ->entity($batch)
                    ->merchant($batch->merchant)
                    ->type($type)
                    ->save()
                    ->getFileInstance();
    }

    /**
     * Gets the input file for processing. Returns the local file path.
     *
     * @param Entity $batch
     *
     * @return string
     */
    protected function getInputFile(Entity $batch): string
    {
        $inputFile = $batch->inputFile();

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
                            ->merchantId($batch->getMerchantId())
                            ->getFile();
        }
        else
        {
            $awsKey = $batch->getFilePrefix(Status::CREATED) . $batch->getFileKeyWithExt();

            $saveAs = $batch->getLocalSavePath(Status::CREATED);

            $filePath = $this->getFileFromAws($awsKey, $saveAs);
        }

        return $filePath;
    }

    /**
     * This function process one batch.
     *
     * @param Entity $batch
     */
    public function process(Entity $batch)
    {
        $this->batch = $batch;

        $this->batch->incrementAttempts();

        $entries = $this->getEntriesToProcess();

        // Set the ttl to MUTEX_LOCK_TIMEOUT sec.
        $this->mutex->acquireAndRelease(
            $batch->getId(),
            function() use ($batch, $entries)
            {
                $this->processBatch($entries);

                $this->createProcessedExcel($entries);

                $this->repo->saveOrFail($this->batch);

                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Processed Batch Refund',
                        'batch'              => $this->batch->toArrayPublic(),
                    ]);
            },
            self::MUTEX_LOCK_TIMEOUT);

        $this->runPostBatchProcessOperations();
    }

    /**
     * This function process batch.
     *
     * @param  array $entries Entries in the batch file
     *
     * @internal param Entity $batch Batch Entity
     */
    protected function processBatch(& $entries)
    {
        $function = 'process' . ucfirst($this->batch->getType()) . 'Entries';
        $this->$function($entries);

        $totalProcessedAmount = 0;
        $totalSuccessCount = 0;
        $totalFailureCount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Header::STATUS] === Status::SUCCESS)
            {
                $totalSuccessCount++;

                $totalProcessedAmount += $entry[Header::REFUNDED_AMOUNT];
            }
            else
            {
                $totalFailureCount++;
            }
        }

        $this->batch->setProcessedAmount($totalProcessedAmount);
        $this->batch->setSuccessCount($totalSuccessCount);
        $this->batch->setFailureCount($totalFailureCount);

        $processedAt = Carbon::now('Asia/Kolkata')->timestamp;

        $this->batch->setProcessedAt($processedAt);

        $this->updateBatchStatus();
    }

    /**
     * This method is for post processing. Once the lock have been released we send out merchant mail, and delete temporary files
     * @return void
     */
    protected function runPostBatchProcessOperations()
    {
        // Send email to merchant if file is processed.
        $this->sendMailIfProcessed();

        // Delete download file from local instance
        $this->deleteFile($this->outputFileLocalPath);

        // Delete upload file from local instance
        $this->deleteFile($this->inputFileLocalPath);
    }

    /**
     * Update the batch status
     * status = processed when failure_count = 0 or attempts >= 3
     * status = processing otherwise
     * @return void
     */
    protected function updateBatchStatus()
    {
        $status = Status::PROCESSED;

        // TODO: Remove this comment. Currently we will mark the final state as processed.
        // if (($this->batch->getFailureCount() > 0) and
        //     ($this->batch->getAttempts() < 3))
        // {
        //     $status = Status::PROCESSING;
        // }

        $this->batch->setStatus($status);
    }

    /**
     * This function process the refund entries.
     * We process only the unprocessed entries.
     * If the payment id doesn't exists in the system then we mark the entry as failure
     *
     * @param  array $entries Array of entries
     *
     * @return void
     */
    protected function processRefundEntries(& $entries)
    {
        foreach ($entries as & $entry)
        {
            if ($this->isEntryProcessed($entry))
            {
                continue;
            }

            $paymentId = $entry[Header::PAYMENT_ID];

            try
            {
                $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->batch->merchant);

                $this->processRefundRequest($payment, $entry);
            }
            catch (Exception\BaseException $e)
            {
                $this->trace->traceException($e, Trace::WARNING, TraceCode::BATCH_PROCESSING_ERROR);

                $error = $e->getError();

                $entry[Header::ERROR_CODE] = $error->getPublicErrorCode();
                $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
                $entry[Header::STATUS] = Status::FAILURE;
            }
        }
    }

    /**
     * This method process individual entry in the batch. Actual refund happens in this.
     * It checks whether a refund exists for the given batch,
     * If it exists then we update the status as success and refund id
     * If the refund is successful then we update the entry with status success and refund id
     * If there is any exception occured, we mark the entry as failed.
     *
     * @param  Payment\Entity $payment Payment Entity
     * @param array           $entry   Single Entry in excel
     */
    protected function processRefundRequest(Payment\Entity $payment, array & $entry)
    {
        $amount = $entry[Header::AMOUNT];

        $processor = new Payment\Processor\Processor($this->batch->merchant);

        $refund = $processor->refundPaymentViaBatchEntry($payment, $this->batch, $amount);

        $entry[Header::REFUND_ID] = $refund->getPublicId();
        $entry[Header::REFUNDED_AMOUNT] = $refund->getAmount();
        $entry[Header::STATUS] = Status::SUCCESS;
        $entry[Header::ERROR_CODE] = null;
        $entry[Header::ERROR_DESCRIPTION] = null;
    }

    /**
     * Method to generate the excel from the processed entries.
     * Also set the downloadFileUrl for the batch
     *
     * @param  array  $entries Array of processed entries
     */
    protected function createProcessedExcel($entries)
    {
        $count = count(Header::REFUND_OUTPUT_HEADERS);

        $finalEntries = [];

        foreach ($entries as $entry)
        {
            $dict = array_combine(Header::REFUND_OUTPUT_HEADERS, array_fill(0, $count, null));

            foreach ($entry as $key => $value)
            {
                $dict[$key] = $value;
            }

            $finalEntries[] = $dict;
        }

        $excel = $this->createExcelObject($finalEntries, $this->batch->getId(), [], $this->batch->getType());

        $storagePath = $this->batch->getLocalSaveDir();

        $fileMetadata = $excel->store('xlsx', $storagePath, true);

        $fullpath = $fileMetadata['full'];

        $this->saveOutputFile($this->batch, $fullpath);

        $this->outputFileLocalPath = $fullpath;
    }

    public function deleteFile($filePath)
    {
        return;

        if (file_exists($filePath))
        {
            $success = unlink($filePath);

            $this->trace->info(TraceCode::BATCH_FILE_DELETE,
                [
                    'success' => $success,
                    'file_path' => $filePath
                ]);
        }
    }

    protected function getEntriesToProcess()
    {
        $filePath = $this->getInputFile($this->batch);

        $this->inputFileLocalPath = $filePath;

        return $this->parseExcelSheets($filePath);
    }

    /**
     * This method is called for each entry in excel. It checks where we need to ignore it or process it.
     * We need to process the entries in cases
     *    - If the status is not set (for first time processing)
     *    - If the status is success
     *    - If the status is failure and the error code are defined (amount_fully_refunded, refund_amount_greater_than_captured)
     * @param  [type]  $entry [description]
     * @return boolean        [description]
     */
    protected function isEntryProcessed($entry)
    {
        $userErrorCodes = [
            PublicErrorDescription::BAD_REQUEST_PAYMENT_FULLY_REFUNDED,
            PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        ];

        if (empty($entry[Header::STATUS]))
        {
            return false;
        }

        // Refund has already been made and the refund id is set
        if ($entry[Header::STATUS] === Status::SUCCESS)
        {
            return true;
        }

        // The complete refund for the payment has already been done
        if (($entry[Header::STATUS] === Status::FAILURE) and
            (in_array($entry[Header::ERROR_DESCRIPTION], $userErrorCodes)))
        {
            return true;
        }

        return false;
    }

    protected function sendMailIfProcessed()
    {
        $batch = $this->batch;

        $filePath = $this->outputFileLocalPath;

        if ($batch->isProcessed() === false)
        {
            return;
        }

        $data = [
            'refundFile' => $filePath,
            'body'       => 'Please find attached processed Refunds File',
            'emails'     => $batch->merchant->getTransactionReportEmail(),
        ];

        Mail::send('emails.message', $data, function($message) use ($data)
        {
            $emails = $data['emails'];

            $message->from('refunds@razorpay.com', 'Refunds File');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('Razorpay | Processed Refunds file for  ' . $today);

            $message->to($emails);

            $message->attach($data['refundFile']);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::BATCH_REFUNDS_FILE);
        });
    }
}
