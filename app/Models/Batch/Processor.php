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

    public function process($batch)
    {
        $filePath = $this->getBatchFileFromAws($batch);

        $entries = $this->parseExcelSheets($filePath);

        // Set the ttl to 1000 sec.
        $this->mutex->acquireAndRelease(
            $batch->getId(),
            function() use ($batch, $entries)
            {
                $this->processBatch($batch, $entries);

                $fullpath = $this->createProcessedExcel($batch, $entries);

                $this->updateBatchStatus($batch);

                $downloadUrl = $this->saveBatchFileToAws($batch, $fullpath);

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
                        'Sending Email'      => $shouldSendMail
                    ]);

                if ($shouldSendMail)
                {
                    $this->sendMail($fullpath, $batch->merchant);
                }

                $this->deleteFile($fullpath);
            },
            1000);

        $this->deleteFile($filePath);
    }

    protected function processBatch($batch, & $entries)
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
        $batch->incrementAttempts();
    }

    protected function shouldSendMail($batch)
    {
        if ($batch->getStatus() === Status::PROCESSED)
        {
            return true;
        }

        return false;
    }

    protected function updateBatchStatus($batch)
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

    protected function processRefundEntries($batch, & $entries)
    {
        foreach ($entries as & $entry)
        {
            if ((empty($entry[Header::STATUS]) === false) and
                ($this->isEntryProcessed($entry)))
            {
                continue;
            }

            $paymentId = $entry[Header::PAYMENT_ID];

            Payment\Entity::verifyIdAndStripSign($paymentId);

            try
            {
                $payment = $this->repo->payment->findByIdAndMerchantId($paymentId, $batch->getMerchantId());

                $this->processRefundRequest($batch, $payment, $entry);
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e, Trace::WARNING, TraceCode::BATCH_PROCESSING_ERROR);

                $error = $e->getError();

                $entry[Header::ERROR_CODE] = $error->getPublicErrorCode();
                $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
                $entry[Header::STATUS] = Status::FAILURE;
            }
        }
    }

    protected function findExistingRefund($batch, $payment)
    {
        // This ensure that if that batch entity is already processed, we update the refund id
        $refunds = $this->repo->refund->fetchRefundsByBatchAndPayment($batch, $payment);

        $count = count($refunds);

        if ($count > 0)
        {
            $this->trace->error (
                TraceCode::BATCH_ALREADY_PROCESSED,
                [
                    'message' => 'Batch entry already processed',
                    'batch'   => $batch->getId(),
                    'refunds' => $refunds->toArrayPublic()
                ]);

            assert($count === 1);

            return $refunds[0];
        }

        return null;
    }

    protected function processRefundRequest($batch, $payment, & $entry)
    {
        $refund = $this->findExistingRefund($batch, $payment);

        if ($refund === null)
        {
            try
            {
                $refundRequest = [
                    'amount' => (string) $entry[Header::AMOUNT],
                ];

                $merchant = $batch->merchant;

                $refund = $this->getNewProcessor($merchant)->refundCapturedPayment(
                    $payment->getPublicId(),
                    $refundRequest);

                $refund->batch()->associate($batch);

                $this->repo->saveOrFail($refund);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, Trace::WARNING, TraceCode::BATCH_PROCESSING_ERROR);

                $error = $e->getError();

                $entry[Header::ERROR_CODE] = $error->getPublicErrorCode();
                $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
                $entry[Header::STATUS] = Status::FAILURE;

                return;
            }
        }

        $entry[Header::REFUND_ID] = $refund->getId();
        $entry[Header::REFUNDED_AMOUNT] = $refund->getAmount();
        $entry[Header::STATUS] = Status::SUCCESS;
    }

    protected function createProcessedExcel($batch, $entries)
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

        $excel = $this->createExcelObject($dict, $batch->getId(), [], $batch->getType());

        $storagePath = $this->getStoragePath();

        $fileMetadata = $excel->store('xlsx', $storagePath, true);

        $fullpath = $fileMetadata['full'];

        return $fullpath;
    }


    public function saveBatchFileToAws($batch, $file)
    {
        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $awsKey = $this->getAwsKey($batch);

        $url = $this->saveToAws($awsKey, $file, $xlsxMimeType);

        return $url;
    }

    protected function getBatchFileFromAws($batch)
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

    public function getBucketFilePath($batch)
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

    public function getFileName($batch)
    {
        return $batch->getId() .'.xlsx';
    }

    public function getAwsKey($batch)
    {
        $bucketFilePath = $this->getBucketFilePath($batch);

        $filename =$this->getFileName($batch);

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
                    'filepath' => $filePath
                ]);
        }
    }

    protected function getNewProcessor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
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

    protected function sendMail($filePath, $merchant)
    {
        $data = [
            'refundFile' => $filePath,
            'body'       => 'Please find attached processed Refunds File',
            'emails'     => $merchant->getTransactionReportEmailAttribute(),
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
