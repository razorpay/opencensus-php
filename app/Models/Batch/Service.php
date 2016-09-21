<?php

namespace RZP\Models\Batch;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use RZP\Models\Batch\Status;

class Service extends Base\Service
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    protected $merchant;

    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();
    }

    public function uploadBatchFile($input)
    {
        $this->validator->validateInputFile($input);

        $entries = $this->parseExcelFile($input['file']);

        $this->validator->validateEntries($entries, $input['type']);

        $uploadFunction = 'upload' .ucfirst($input['type']);
        return $this->$uploadFunction($input['file'], $entries);
    }

    public function getBatchFiles($input)
    {
        /*
        $this->validator->validateType($input);

        $skip = 0;
        if (empty($input['skip']) === false)
        {
            $skip = $input['skip'];
        }

        $take = 10;
        if (empty($input['take']) === false)
        {
            $take = $input['take'];
        }

        $merchant = $this->merchant;
        $batches = $this->repo->batch->getBatches($merchant->getId(), $input['type'], $skip, $take);

        */

        $merchant = $this->merchant;
        $batches = $this->repo->batch->fetch($input, $merchant->getId());
        $this->trace->info(TraceCode::BATCH_LIST, $batches->toArrayPublic());

        return $batches->toArrayPublic();
    }

    public function downloadBatchFile($id)
    {
        $batch = $this->repo->batch->findOrFail($id);

        $publicUrl = '';

        $storagePath = storage_path('files/batch_file_download');
        $filePath = $storagePath . '/' . $batch->getId() . '.xlsx';

        if ($batch->getStatus() === Status::CREATED)
        {
            $publicUrl = $this->getPreSignedUrlFromAws('batch_file_download_bucket', $id.'.xlsx', $filePath);
        }
        else
        {
            $publicUrl = $this->getPreSignedUrlFromAws('batch_file_upload_bucket', $id.'.xlsx', $filePath);
        }

        $response = [
            'url' => $publicUrl,
        ];

        $this->trace->info(
            TraceCode::BATCH_DOWNLOAD,
            [
                'message'       => 'Downloading the batch file',
                'batch'         => $batch->toArrayPublic(),
                'url'           => $publicUrl,
            ]);

        return $response;
    }

    public function retryBatchFile($id)
    {
        $batch = $this->repo->batch->findOrFail($id);

        if ($batch->getStatus() === Status::PROCESSED)
        {
            $this->trace->error(
                TraceCode::BATCH_RETRY,
                [
                    'message'       => 'Retry cannot be done for the already processed file',
                    'batch'         => $batch->toArrayPublic(),
                ]);

            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FILE_ALREADY_PROCESSED);
        }

        $batch->setStatus(Status::IN_PROGRESS);
        $batch->setAttempts(0);
        $this->repo->saveOrFail($batch);

        $this->trace->info(
            TraceCode::BATCH_RETRY,
            [
                'message'       => 'Submitted batch file for retry',
                'batch'         => $batch->toArrayPublic(),
            ]);

        return $batch->toArrayPublic();
    }

    public function processBatchFiles()
    {
        $batches = $this->repo->batch->findUnprocessedEntries();

        foreach ($batches as $batch)
        {
            $storagePath = storage_path('files/batch_file_download');
            $filePath = $storagePath . '/' . $batch->getId() . '.xlsx';

            if ($batch->getStatus() === Status::CREATED)
            {
                $filePath = $this->getFileFromAws('batch_file_upload_bucket', $batch->getId().'.xlsx', $filePath);
            }

            elseif ($batch->getStatus() === Status::FAILURE)
            {
                $filePath = $this->getFileFromAws('batch_file_download_bucket', $batch->getId().'.xlsx', $filePath);
            }

            $entries = $this->parseExcelFile($filePath);

            $processFunction = 'process' .ucfirst($batch->getType());
            $processedObj = $this->$processFunction($batch, $entries);

            $processedFile = $processedObj['processedFile'];
            $batch = $processedObj['batch'];
            $shouldSendMail = $processedObj['shouldSendMail'];

            $excel = $this->createExcelObject($processedFile, $batch->getId());

            $fileMetadata = $excel->store('xlsx', storage_path('files/batch_file_download'), true);
            $fullpath = $fileMetadata['full'];

            $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $downloadUrl = $this->saveToAws($batch->getId().'.xlsx', $fullpath, $xlsxMimeType, 'batch_file_download_bucket');

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

        return $batches->toArrayPublic();
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

    private function sendMail($filePath, $merchant)
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
    }

    private function uploadRefund($file, $entries)
    {
        $totalAmountToBeRefunded = 0;
        $totalEntries = count($entries);

        $headers = array('payment_id', 'refund_amount');

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $amount = $entryMap['refund_amount'];
            $totalAmountToBeRefunded += $amount;
        }

        $merchant = $this->merchant;
        $balance = $this->repo->balance->getMerchantBalance($merchant);
        $balanceAmount = $balance->getBalance();

        if ($totalAmountToBeRefunded > $balanceAmount)
        {
            $this->trace->info(
                TraceCode::BATCH_UPLOAD_FILE,
                [
                    'message'            => 'The merchant balance is lesser than the total refund amount.',
                    'merchantBalance'    => $balanceAmount,
                    'totalRefundAmount'  => $totalAmountToBeRefunded,
                ]);
        }

        $batchRefund = [
            'total_count'           => $totalEntries,
            'type'                  => Type::REFUND,
        ];

        return $this->saveBatch($batchRefund, $file, $merchant);
    }

    private function saveBatch($batchRefund, $file, $merchant)
    {
        $batchRefund = (new Entity)->build($batchRefund);

        $batchRefund->merchant()->associate($merchant);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($batchRefund->getId().'.xlsx',$file, $xlsxMimeType, 'batch_file_upload_bucket');

        $batchRefund->setUploadFileUrl($url);

        $this->repo->saveOrFail($batchRefund);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batchRefund->toArrayPublic());

        return $batchRefund->toArrayPublic();
    }

    private function processRefund($batch, $entries)
    {
        $headers = array();
        if ($batch->getStatus() === Status::CREATED)
        {
            array_push($headers, 'payment_id', 'refund_amount');
        }
        elseif ($batch->getStatus() === Status::FAILURE)
        {
            array_push($headers, 'payment_id', 'refund_amount', 'refund_id', 'refunded_amount', 'status', 'comment');
        }

        $processedFile = array();
        $totalRefundedAmount = $batch->getAmount();
        $totalSuccessCount = $batch->getSuccessCount();
        $totalFailureCount = 0;

        $this->trace->info(
            TraceCode::BATCH_PROCESS_FILE,
            [
                'message'            => 'Processing Batch file',
                'batch'              => $batch->toArrayPublic(),
            ]);

        foreach ($entries as $entry)
        {
            $batchRefundEntry = array();

            $entryMap = array_combine($headers, $entry);

            // Refund has already been made and the refund id is set
            if (!empty($entryMap['refund_id']))
            {
                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Already processed entry',
                        'payemntId'          => $entryMap['payment_id'],
                        'amount'             => $entryMap['refund_amount'],
                        'refundId'           => $entryMap['refund_id'],
                    ]);

                array_push($processedFile, $entry);
                continue;
            }

            // The complete refund for the payment has already been done
            if (isset($entryMap['status']) && $entryMap['status'] === Status::FAILURE &&
                ($entryMap['comment'] === 'BAD_REQUEST_PAYMENT_FULLY_REFUNDED' || $entryMap['comment'] === 'BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED'))
            {
                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Error in processing the entity',
                        'payemntId'          => $entryMap['payment_id'],
                        'amount'             => $entryMap['refund_amount'],
                        'status'             => $entryMap['status'],
                        'comment'            => $entryMap['comment'],
                    ]);

                $totalFailureCount++;
                array_push($processedFile, $entry);
                continue;
            }

            $paymentId = $entryMap['payment_id'];
            $amount = $entryMap['refund_amount'];

            $refundRequest = [
                'amount' => (int) $amount,
            ];

            array_push($batchRefundEntry, $paymentId, $amount);

            try
            {
                $merchant = $batch->merchant;
                $refund = $this->getNewProcessor($merchant)->refundCapturedPayment($paymentId, $refundRequest);

                $refund->batch()->associate($batch);
                $this->repo->saveOrFail($refund);

                array_push($batchRefundEntry, $refund->getId(), $refund->getAmount(), Status::PROCESSED, Status::PROCESSED);

                $totalSuccessCount++;
                $totalRefundedAmount += $refund->getAmount();

                $this->trace->info(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Refund Successfully',
                        'payemntId'          => $entryMap['payment_id'],
                        'amount'             => $entryMap['refund_amount'],
                        'refundId'           => $refund->getId(),
                        'refundedAmount'     => $refund->getAmount(),
                        ]);
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::BATCH_PROCESS_FILE,
                    [
                        'message'            => 'Refund was not successfull',
                        'payemntId'          => $entryMap['payment_id'],
                        'amount'             => $entryMap['refund_amount'],
                        'errorMessage'       => $e->getCode(),
                    ]);

                array_push($batchRefundEntry, '', '', Status::FAILURE, $e->getCode());
                $totalFailureCount++;
            }
            array_push($processedFile, $batchRefundEntry);
        }

        $batch->setAmount($totalRefundedAmount);
        $batch->setSuccessCount($totalSuccessCount);
        $batch->setFailureCount($totalFailureCount);

        $retryAttempts = $batch->getAttempts() + 1;
        $batch->setAttempts($retryAttempts);

        $shouldSendMail = false;

        if ($batch->getFailureCount() > 0)
        {
            if ($batch->getAttempts() == 3)
            {
                $batch->setStatus(Status::FAILED);
                $shouldSendMail = true;
            }
            else
            {
                $batch->setStatus(Status::FAILURE);
            }
        }
        else
        {
            $batch->setStatus(Status::PROCESSED);
            $shouldSendMail = true;
        }

        $returnObj = [
            'batch'             => $batch,
            'shouldSendMail'    => $shouldSendMail,
            'processedFile'     => $processedFile
        ];

        return $returnObj;
    }
}
