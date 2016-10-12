<?php

namespace RZP\Models\Batch;

use Mail;
use Config;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Processor extends Base\Core
{
    use FileHandlerTrait;

    protected $mutex;

    protected $batch;

    protected $uploadFileLocalPath;
    protected $downloadFileLocalPath;

    const MUTEX_LOCK_TIMEOUT = 2500;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * This function process one batch.
     * @param  Batch\Entity $batch batch entity
     * @return void
     */
    public function process(Batch\Entity $batch)
    {
        $this->batch = $batch;

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
     * @param  Batch\Entity $batch   Batch Entity
     * @param  array       $entries  Entries in the batch file
     * @return void
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
        $this->deleteFile($this->downloadFileLocalPath);

        // Delete upload file from local instance
        $this->deleteFile($this->uploadFileLocalPath);
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

        if (($this->batch->getFailureCount() > 0) and
            ($this->batch->getAttempts() < 3))
        {
            $status = Status::PROCESSING;
        }

        $this->batch->setStatus($status);
    }

    /**
     * This function process the refund entries.
     * We process only the unprocessed entries.
     * If the payment id doesn't exists in the system then we mark the entry as failure
     * @param  Array        $entries Array of entries
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
            catch (\Exception $e)
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
     * @param  Payment\Entity $payment Payment Entity
     * @param  Array          $entry   Single Entry in excel
     * @return void
     */
    protected function processRefundRequest(Payment\Entity $payment, array & $entry)
    {
        $amount = $entry[Header::AMOUNT];

        $processor = new Payment\Processor\Processor($this->batch->merchant);

        $refund = $processor->refundPaymentViaBatchEntry($payment, $this->batch, $amount);

        $entry[Header::REFUND_ID] = $refund->getPublicId();
        $entry[Header::REFUNDED_AMOUNT] = $refund->getAmount();
        $entry[Header::STATUS] = Status::SUCCESS;
    }

    /**
     * Method to generate the excel from the processed entries.
     * Also set the downloadFileUrl for the batch
     * @param  Array        $entries Array of processed entries
     * @return FilePath              Local file path of the excel file.
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

        $storagePath = $this->getStoragePath();

        $fileMetadata = $excel->store('xlsx', $storagePath, true);

        $fullpath = $fileMetadata['full'];

        $downloadUrl = $this->saveBatchFileToAws($this->batch, $fullpath);

        $this->batch->setDownloadFileUrl($downloadUrl);

        $this->downloadFileLocalPath = $fullpath;
    }

    public function saveBatchFileToAws(Batch\Entity $batch, $file)
    {
        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $awsKey = $this->getAwsKey($batch);

        $url = $this->saveToAws($awsKey, $file, $xlsxMimeType);

        return $url;
    }

    protected function getBatchFileFromAws(Batch\Entity $batch)
    {
        $storagePath = $this->getStoragePath();

        $filename = $this->getFileName($batch);

        $filePath = $storagePath . '/' . $filename;

        $awsKey = $this->getAwsKey($batch);

        return $this->getFileFromAws($awsKey, $filePath);
    }

    public function getStoragePath()
    {
        $path = storage_path('files/batch');

        return $path;
    }

    /**
     * Get the bucket file path.
     * If the batch status is created, then the dir would be batch/upload
     * else the batch would be processing/processed state, then dir would be batch/download
     * @param  Batch\Entity $batch [description]
     * @return [type]              [description]
     */
    public function getBucketFilePath(Batch\Entity $batch)
    {
        if ($batch->getStatus() === Status::CREATED)
        {
            return 'batch/upload';
        }
        else
        {
            return 'batch/download';
        }
    }

    public function getFileName(Batch\Entity $batch)
    {
        return $batch->getId() .'.xlsx';
    }

    public function getAwsKey(Batch\Entity $batch)
    {
        $bucketFilePath = $this->getBucketFilePath($batch);

        $filename = $this->getFileName($batch);

        $awsKey = $bucketFilePath .'/' .$filename;

        return $awsKey;
    }

    public function deleteFile($filePath)
    {
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
        $filePath = $this->getBatchFileFromAws($this->batch);

        $this->uploadFileLocalPath = $filePath;

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

        $filePath = $this->downloadFileLocalPath;

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
        });
    }
}
