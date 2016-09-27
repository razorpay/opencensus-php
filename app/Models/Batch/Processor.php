<?php

namespace RZP\Models\Batch;

use Mail;
use Config;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Processor extends Base\Core
{
    use FileHandlerTrait;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process($batch)
    {
        try
        {
            $this->acquireMutexOnBatch($batch);

            $filePath = $this->getBatchFileFromAws($batch);

            $entries = $this->parseExcelFile($filePath);

            list($batch, $shouldSendMail, $processedFile) = $this->processBatch($batch, $entries);

            $excel = $this->createExcelObject($processedFile, $batch->getId());

            $fileMetadata = $excel->store('xlsx', storage_path('files/batch_file_download'), true);
            $fullpath = $fileMetadata['full'];

            $downloadUrl = $this->saveBatchFileToAws($batch, $fullpath);

            $batch->setDownloadFileUrl($downloadUrl);

            $processedAt = Carbon::today('Asia/Kolkata')->timestamp;
            $batch->setProcessedAt($processedAt);

            $this->repo->saveOrFail($batch);

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
        }
        catch (Exception\BadRequestException $ex)
        {
            throw $ex;
        }
        finally
        {
            $this->releaseMutexOnBatch($batch);
        }

    }

    protected function processBatch($batch, $entries)
    {
        $function = 'process' .ucfirst($batch->getType()) .'Entries';
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

        return array($batch,$shouldSendMail,$processedFile);
    }

    protected function processRefundEntries($batch, $entries)
    {
        $totalRefundedAmount = 0;
        $totalSuccessCount = 0;
        $totalFailureCount = 0;
        $processedFile = array();

        $headers = $this->getHeaders($batch);

        foreach ($entries as $entry)
        {
            $batchRefundEntry = array();

            $entryMap = array_combine($headers, $entry);

            // Refund has already been made and the refund id is set
            if (empty($entryMap['Refund Id']) === false)
            {
                array_push($processedFile, $entry);
                continue;
            }

            // The complete refund for the payment has already been done
            if (isset($entryMap['Status']) === true && $entryMap['Status'] === Status::PROCESSING &&
                ($entryMap['Comment'] === 'BAD_REQUEST_PAYMENT_FULLY_REFUNDED' || $entryMap['Comment'] === 'BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED'))
            {
                $totalFailureCount++;

                array_push($processedFile, $entry);
                continue;
            }

            $paymentId = $entryMap['Payment Id'];
            $amount = $entryMap['Amount'];

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
        try
        {
            $refundRequest = [
                'amount' => (string) $amount,
            ];

            $merchant = $batch->merchant;

            $refund = $this->getNewProcessor($merchant)->refundCapturedPayment($paymentId, $refundRequest);

            $refund->batch()->associate($batch);
            $this->repo->saveOrFail($refund);

            array_push($batchRefundEntry, $refund->getId(), $refund->getAmount(), Status::PROCESSED, Status::PROCESSED);

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

            array_push($batchRefundEntry, '', '', Status::PROCESSING, $e->getCode());

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

        return $this->getFileFromAws($bucket, $batch->getId().'.xlsx', $filePath);
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

    protected function getHeaders($batch)
    {
        if ($batch->getStatus() === Status::CREATED)
        {
            return Batch\Type::getInputHeaders($batch->getType());
        }
        else
        {
            return Batch\Type::getOutputHeaders($batch->getType());
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