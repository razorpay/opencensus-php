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
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
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
        $this->mutex->acquireAndRelease($batch->getId(), function() use ($batch)
        {
            $filePath = $this->getBatchFileFromAws($batch);

            $entries = $this->parseExcelFile($filePath);

            list($batch, $shouldSendMail, $processedFile) = $this->processBatch($batch, $entries);

            $excel = $this->createExcelObject($processedFile, $batch->getId(), [], $batch->getType());

            $fileMetadata = $excel->store('xlsx', storage_path('files/batch_file_download'), true);
            $fullpath = $fileMetadata['full'];

            $downloadUrl = $this->saveBatchFileToAws($batch, $fullpath);

            $batch->setDownloadFileUrl($downloadUrl);

            $processedAt = Carbon::now('Asia/Kolkata')->timestamp;
            $batch->setProcessedAt($processedAt);

            $this->repo->saveOrFail($batch);

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

            $this->trace->info(
                TraceCode::BATCH_PROCESS_FILE,
                [
                    'message'            => 'Processed Batch Refund',
                    'batch'              => $batch->toArrayPublic(),
                ]);
        });
    }

    protected function processBatch($batch, $entries)
    {
        $function = 'process' . ucfirst($batch->getType()) . 'Entries';
        list($totalProcessedAmount, $totalSuccessCount, $totalFailureCount, $processedFile) = $this->$function($batch, $entries);

        $totalProcessedAmount += $batch->getProcessedAmount();
        $totalSuccessCount += $batch->getSuccessCount();

        $batch->setProcessedAmount($totalProcessedAmount);
        $batch->setSuccessCount($totalSuccessCount);
        $batch->setFailureCount($totalFailureCount);

        $retryAttempts = $batch->getAttempts() + 1;
        $batch->setAttempts($retryAttempts);

        $shouldSendMail = false;

        if ($batch->getFailureCount() > 0)
        {
            if ($batch->getAttempts() >= 3)
            {
                $batch->setStatus(Status::PROCESSED);
                $shouldSendMail = true;
            }
            else
            {
                $batch->setStatus(Status::PROCESSING);
            }
        }
        else
        {
            $batch->setStatus(Status::PROCESSED);
            $shouldSendMail = true;
        }

        return array($batch, $shouldSendMail, $processedFile);
    }

    protected function processRefundEntries($batch, $entries)
    {
        $totalRefundedAmount = 0;
        $totalSuccessCount = 0;
        $totalFailureCount = 0;
        $processedFile = array();

        foreach ($entries as $entry)
        {
            $batchRefundEntry = array();

            // Refund has already been made and the refund id is set
            if (empty($entry['refund_id']) === false)
            {
                array_push($processedFile, $entry);
                continue;
            }

            // The complete refund for the payment has already been done
            if ((isset($entry['status']) === true) and ($entry['status'] === Status::FAILURE) and
                (($entry['error_description'] === PublicErrorDescription::BAD_REQUEST_PAYMENT_FULLY_REFUNDED) or
                    ($entry['error_description'] === PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED)))
            {
                $totalFailureCount++;

                array_push($processedFile, $entry);
                continue;
            }

            $paymentId = $entry['payment_id'];
            $amount = $entry['amount'];

            array_push($batchRefundEntry, $paymentId, $amount);

            list($batchEntry, $isSuccess, $refundAmount) = $this->processRefundRequest($batch, $paymentId, $amount, $batchRefundEntry);

            if($isSuccess === true)
            {
                $totalSuccessCount += 1;
                $totalRefundedAmount += $refundAmount;
            }
            else
            {
                $totalFailureCount += 1;
            }

            array_push($processedFile, $batchEntry);
        }

        return array($totalRefundedAmount, $totalSuccessCount, $totalFailureCount, $processedFile);
    }

    protected function processRefundRequest($batch, $paymentId, $amount, $batchRefundEntry)
    {
        // This ensure that if that batch entity is already processed, we update the refund id
        $refunds = $this->repo->refund->fetchByBatchIdPaymentIdMerchantIdAmount($batch->getId(), $paymentId, $batch->getMerchantId(), $amount);

        if(count($refunds) > 0)
        {
            $this->trace->error(
                TraceCode::BATCH_PROCESS_FILE,
                [
                    'message'            => 'Batch entry already processed',
                    'payemntId'          => $paymentId,
                    'amount'             => $amount,
                    'refunds'            => $refunds->toArrayPublic()
                ]);

            $refund = $refunds[0];

            array_push($batchRefundEntry, $refund->getId(), $refund->getAmount(), Status::SUCCESS, '', '');

            return array($batchRefundEntry, true, $refund->getAmount());
        }

        try
        {
            $refundRequest = [
                'amount' => (string) $amount,
            ];

            $merchant = $batch->merchant;

            $refund = $this->getNewProcessor($merchant)->refundCapturedPayment($paymentId, $refundRequest);

            $refund->batch()->associate($batch);
            $this->repo->saveOrFail($refund);

            array_push($batchRefundEntry, $refund->getId(), $refund->getAmount(), Status::SUCCESS, '', '');

            return array($batchRefundEntry, true, $refund->getAmount());
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::BATCH_PROCESS_FILE,
                [
                    'message'            => 'Refund was not successfull',
                    'payemntId'          => $paymentId,
                    'amount'             => $amount,
                    'errorMessage'       => $e->getCode(),
                ]);

            $error = $e->getError();
            array_push($batchRefundEntry, '', '', Status::FAILURE, $error->getPublicErrorCode(), $error->getDescription());

            return array($batchRefundEntry, false, 0);
        }
    }

    protected function saveBatchFileToAws($batch, $file)
    {
        $bucket = $this->getBucketName($batch);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $url = $this->saveToAws($batch->getId().'.xlsx', $file, $xlsxMimeType, $bucket);

        return $url;
    }

    protected function getBatchFileFromAws($batch)
    {
        $storagePath = storage_path('files/batch_file_download');
        $filePath = $storagePath . '/' . $batch->getId() . '.xlsx';

        $bucket = $this->getBucketName($batch);

        return $this->getFileFromAws($bucket, $batch->getId() . '.xlsx', $filePath);
    }

    protected function getBucketName($batch)
    {
        if ($batch->getStatus() === Status::CREATED)
        {
            return 'batch_upload_bucket';
        }
        else
        {
            return 'batch_download_bucket';
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

    protected function acquireMutexOnBatch($batch)
    {
        $resource = $batch->getId();

        if ($this->mutex->acquire($resource) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS);
        }
    }

    protected function releaseMutexOnBatch($batch)
    {
        $this->mutex->release($batch->getId());
    }

    protected function sendMail($filePath, $merchant)
    {
        $data = [
            'refundFile' => $filePath,
            'body' => 'Please find attached processed Refunds File',
            'emails' => array_merge($merchant->getTransactionReportEmailAttribute(), array('settlements@razorpay.com')),
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
    }}
