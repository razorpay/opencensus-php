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

    public function process(Batch\Entity $batch)
    {
        $this->batch = $batch;

        $entries = $this->getEntriesToProcess();

        // Set the ttl to MUTEX_LOCK_TIMEOUT sec.
        $this->mutex->acquireAndRelease(
            $batch->getId(),
            function() use ($batch, $entries)
            {
                $this->processBatch($batch, $entries);

                $this->createProcessedExcel($batch, $entries);

                $this->repo->saveOrFail($batch);

                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Processed Batch Refund',
                        'batch'              => $batch->toArrayPublic(),
                    ]);
            },
            self::MUTEX_LOCK_TIMEOUT);

        $this->runPostBatchProcessOperations();

        return $batch;
    }

    protected function processBatch(Batch\Entity $batch, & $entries)
    {
        $function = 'process' . ucfirst($batch->getType()) . 'Entries';
        $this->$function($batch, $entries);

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

        $batch->setProcessedAmount($totalProcessedAmount);
        $batch->setSuccessCount($totalSuccessCount);
        $batch->setFailureCount($totalFailureCount);

        $processedAt = Carbon::now('Asia/Kolkata')->timestamp;

        $batch->setProcessedAt($processedAt);

        $this->updateBatchStatus($batch);
    }

    protected function runPostBatchProcessOperations()
    {
        // Send email to merchant if file is processed.
        $this->sendMailIfProcessed();

        // Delete download file from local instance
        $this->deleteFile($this->downloadFileLocalPath);

        // Delete upload file from local instance
        $this->deleteFile($this->uploadFileLocalPath);
    }

    protected function updateBatchStatus(Batch\Entity $batch)
    {
        $status = Status::PROCESSED;

        if (($batch->getFailureCount() > 0) and
            ($batch->getAttempts() < 3))
        {
            $status = Status::PROCESSING;
        }

        $batch->setStatus($status);
    }

    protected function processRefundEntries(Batch\Entity $batch, & $entries)
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
                $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $batch->merchant);

                $this->processRefundRequest($batch, $payment, $entry);
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

    protected function processRefundRequest(Batch\Entity $batch, Payment\Entity $payment, array & $entry)
    {
        $amount = $entry[Header::AMOUNT];

        $processor = new Payment\Processor\Processor($batch->merchant);

        $refund = $processor->refundPaymentViaBatchEntry($payment, $batch, $amount);

        $entry[Header::REFUND_ID] = $refund->getPublicId();
        $entry[Header::REFUNDED_AMOUNT] = $refund->getAmount();
        $entry[Header::STATUS] = Status::SUCCESS;
    }

    protected function createProcessedExcel(Batch\Entity $batch, $entries)
    {
        $count = count(Header::REFUND_HEADERS);

        $finalEntries = [];

        foreach ($entries as $entry)
        {
            $dict = array_combine(Header::REFUND_HEADERS, array_fill(0, $count, null));

            foreach ($entry as $key => $value)
            {
                $dict[$key] = $value;
            }

            $finalEntries[] = $dict;
        }

        $excel = $this->createExcelObject($finalEntries, $batch->getId(), [], $batch->getType());

        $storagePath = $this->getStoragePath();

        $fileMetadata = $excel->store('xlsx', $storagePath, true);

        $fullpath = $fileMetadata['full'];

        $downloadUrl = $this->saveBatchFileToAws($batch, $fullpath);

        $batch->setDownloadFileUrl($downloadUrl);

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
