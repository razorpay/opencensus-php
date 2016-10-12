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

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process(Batch\Entity $batch)
    {
        $filePath = $this->getBatchFileFromAws($batch);

        $entries = $this->parseExcelSheets($filePath);

        // Set the ttl to 1000 sec.
        $this->mutex->acquireAndRelease(
            $batch->getId(),
            function() use ($batch, $entries)
            {
                $this->processBatch($batch, $entries);

                $fullPath = $this->createProcessedExcel($batch, $entries);

                $this->updateBatchStatus($batch);

                $downloadUrl = $this->saveBatchFileToAws($batch, $fullPath);

                $batch->setDownloadFileUrl($downloadUrl);

                $processedAt = Carbon::now('Asia/Kolkata')->timestamp;

                $batch->setProcessedAt($processedAt);

                $this->repo->saveOrFail($batch);

                $shouldSendMail = $this->shouldSendMail($batch);

                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Processed Batch Refund',
                        'batch'              => $batch->toArrayPublic(),
                        'sending_email'      => $shouldSendMail
                    ]);

                if ($shouldSendMail === true)
                {
                    $this->sendMail($fullPath, $batch->merchant);
                }

                $this->deleteFile($fullPath);
            },
            1000);

        $this->deleteFile($filePath);
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
            if (empty($entry[Header::STATUS]) === false)
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
        }

        $batch->setProcessedAmount($totalProcessedAmount);
        $batch->setSuccessCount($totalSuccessCount);
        $batch->setFailureCount($totalFailureCount);
    }

    protected function shouldSendMail(Batch\Entity $batch)
    {
        if ($batch->getStatus() === Status::PROCESSED)
        {
            return true;
        }

        return false;
    }

    protected function updateBatchStatus(Batch\Entity $batch)
    {
        if ($batch->getFailureCount() > 0)
        {
            if ($batch->getAttempts() >= 3)
            {
                $batch->setStatus(Status::PROCESSED);
            }
            else
            {
                $batch->setStatus(Status::PROCESSING);
            }
        }
        else
        {
            $batch->setStatus(Status::PROCESSED);
        }
    }

    protected function processRefundEntries(Batch\Entity $batch, & $entries)
    {
        foreach ($entries as & $entry)
        {
            if ((empty($entry[Header::STATUS]) === false) and
                ($this->isEntryProcessed($entry)))
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

        return $fullpath;
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

    protected function isEntryProcessed($entry)
    {
        $userErrorCodes = [
            PublicErrorDescription::BAD_REQUEST_PAYMENT_FULLY_REFUNDED,
            PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        ];

        // Refund has already been made and the refund id is set
        if (empty($entry[Header::STATUS]) === Status::SUCCESS)
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

    protected function sendMail($filePath, Merchant\Entity $merchant)
    {
        $data = [
            'refundFile' => $filePath,
            'body'       => 'Please find attached processed Refunds File',
            'emails'     => $merchant->getTransactionReportEmail(),
        ];

        Mail::send('emails.message', $data, function($message) use ($data)
        {
            $emails = $data['emails'];

            $message->from('refunds@razorpay.com', 'Refunds File');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('Processed Refunds file for  ' . $today);

            $message->to($emails);

            $message->attach($data['refundFile']);
        });
    }
}
