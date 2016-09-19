<?php

namespace RZP\Models\Payment\BatchRefund;

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
use RZP\Models\Payment\BatchRefund\BatchRefundStatus;

class Service extends Base\Service
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Refund_File';

    protected $merchant;

    public function uploadRefundFile($input)
    {
        $entries = $this->parseExcelFile($input['file']);

        $totalEntries = count($entries);

        if ($totalEntries > 1000){
              throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_FILE_EXCEED_LIMIT);
        }

        $totalAmountToBeRefunded = 0;

        $headers = array('payment_id', 'refund_amount');

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $amount = $entryMap['refund_amount'];

            if (isset($amount))
            {
                $totalAmountToBeRefunded += $amount;
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_REFUND_FILE_VALIDATION);
            }
        }

        $merchant = $this->merchant;

        $balance = $this->repo->balance->getMerchantBalance($merchant);
        $balanceAmount = $balance->getBalance();

        $response = [
            'message' => 'Successfully uploaded the file.',
        ];

        if ($totalAmountToBeRefunded > $balanceAmount)
        {
            // Warning to merchant about insufficient balance
            $response['message'] =  'Successfully uploaded the file. The total refund amoount is greater than your balance.';
        }

        $batchRefund = [
            'total_count' => $totalEntries,
        ];

        $batchRefund = (new Entity)->build($batchRefund);

        $batchRefund->merchant()->associate($merchant);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($batchRefund->getId().'.xlsx', $input['file'], $xlsxMimeType, 'refund_file_upload_bucket');

        $batchRefund->setUploadFileUrl($url);

        $this->repo->saveOrFail($batchRefund);

        $response['entity'] = $batchRefund->toArrayPublic();

        return $response;
    }

    public function processRefundFile()
    {
        $batchRefunds = $this->repo->batch_refund->findUnprocessedRefunds();

        $headers = array();

        foreach ($batchRefunds as $batchRefund)
        {
            $storagePath = storage_path('files/refund_file_download');
            $filePath = $storagePath . '/' . $batchRefund->getId() . '.xlsx';

            if ($batchRefund->getStatus() === BatchRefundStatus::CREATED)
            {
                $filePath = $this->getFileFromAws('refund_file_upload_bucket', $batchRefund->getId().'.xlsx', $filePath);
                array_push($headers, 'payment_id', 'refund_amount');
            }

            elseif ($batchRefund->getStatus() === BatchRefundStatus::FAILURE)
            {
                $filePath = $this->getFileFromAws('refund_file_download_bucket', $batchRefund->getId().'.xlsx', $filePath);
                array_push($headers, 'payment_id', 'refund_amount', 'refund_id', 'refunded_amount', 'status', 'comment');
            }

            $entries = $this->parseExcelFile($filePath);

            $totalRefundedAmount = $batchRefund->getAmount();
            $totalSuccessCount = $batchRefund->getSuccessCount();
            $totalFailureCount = 0;

            $processedFile = array();

            foreach ($entries as $entry)
            {
                $batchRefundEntry = array();

                $entryMap = array_combine($headers, $entry);

                // Refund has already been made and the refund id is set
                if (!empty($entryMap['refund_id']))
                {
                    array_push($processedFile, $entry);
                    continue;
                }

                // The complete refund for the payment has already been done
                if (isset($entryMap['status']) && $entryMap['status'] === BatchRefundStatus::FAILURE &&
                    ($entryMap['comment'] === 'BAD_REQUEST_PAYMENT_FULLY_REFUNDED' || $entryMap['comment'] === 'BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED'))
                {
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
                    $merchant = $batchRefund->merchant;
                    $refund = $this->getNewProcessor($merchant)->refundCapturedPayment($paymentId, $refundRequest);

                    array_push($batchRefundEntry, $refund->getId(), $refund->getAmount(), BatchRefundStatus::PROCESSED);

                    $totalSuccessCount++;
                    $totalRefundedAmount += $refund->getAmount();

                } catch (\Exception $e)
                {
                    array_push($batchRefundEntry, '', '', BatchRefundStatus::FAILURE, $e->getCode());

                    $totalFailureCount++;
                }

                array_push($processedFile, $batchRefundEntry);
            }

            $batchRefund->setAmount($totalRefundedAmount);
            $batchRefund->setSuccessCount($totalSuccessCount);
            $batchRefund->setFailureCount($totalFailureCount);

            $retryAttempts = $batchRefund->getAttempts() + 1;
            $batchRefund->setAttempts($retryAttempts);

            $shouldSendMail = false;

            if ($totalFailureCount > 0)
            {
                if ($retryAttempts == 3)
                {
                    $batchRefund->setStatus(BatchRefundStatus::FAILED);
                    $shouldSendMail = true;
                }
                else
                {
                    $batchRefund->setStatus(BatchRefundStatus::FAILURE);
                }
            }
            else
            {
                $batchRefund->setStatus(BatchRefundStatus::PROCESSED);
                $shouldSendMail = true;
            }

            $excel = $this->createExcelObject($processedFile, $batchRefund->getId());

            $fileMetadata = $excel->store('xlsx', storage_path('files/refund_file_download'), true);
            $fullpath = $fileMetadata['full'];

            $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $downloadUrl = $this->saveToAws($batchRefund->getId().'.xlsx', $fullpath, $xlsxMimeType, 'refund_file_download_bucket');

            $batchRefund->setDownloadFileUrl($downloadUrl);

            $processedAt = Carbon::today('Asia/Kolkata')->timestamp;
            $batchRefund->setProcessedAt($processedAt);

            $this->repo->saveOrFail($batchRefund);

            if ($shouldSendMail)
            {
                $this->sendMail($fullpath, $totalRefundedAmount, $batchRefund->merchant);
            }
        }

        return $batchRefunds->toArrayPublic();
    }

    public function getBatchRefunds($input)
    {
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
        $batchRefunds = $this->repo->batch_refund->getBatchRefunds($merchant->getId(), $skip, $take);

        return $batchRefunds->toArrayPublic();

    }

    public function retryBatchRefund($id)
    {
        $batchRefund = $this->repo->batch_refund->findOrFail($id);

        if ($batchRefund->getStatus() === BatchRefundStatus::PROCESSED)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_REFUND_FILE_ALREADY_PROCESSED);
        }

        $batchRefund->setStatus(BatchRefundStatus::IN_PROGRESS);

        $batchRefund->setAttempts(0);

        $this->repo->saveOrFail($batchRefund);

        return $batchRefund->toArrayPublic();

    }

    public function downloadBatchRefund($id)
    {

        $batchRefund = $this->repo->batch_refund->findOrFail($id);

        $publicUrl = '';

        $storagePath = storage_path('files/refund_file_download');
        $filePath = $storagePath . '/' . $batchRefund->getId() . '.xlsx';

        if ($batchRefund->getStatus() === BatchRefundStatus::CREATED)
        {
            $publicUrl = $this->getPreSignedUrlFromAws('refund_file_download_bucket', $id.'.xlsx', $filePath);
        }
        else
        {
            $publicUrl = $this->getPreSignedUrlFromAws('refund_file_upload_bucket', $id.'.xlsx', $filePath);
        }

        $response = [
            'url' => $publicUrl,
        ];

        return $response;
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

    protected function sendMail($filePath, $amount, $merchant)
    {
        //TODO: Get new blade for the refund mail which will have the attached file
        // $data = [
        //     'refundFile' => $filePath,
        //     'subject' => 'subject',
        //     'amounts' => $amount,
        //     'merchant' => $merchant

        // ];

        // Mail::send('emails.refund.common', $data, function($message) use ($data)
        // {
        //     $emails = ['settlements@razorpay.com'];

        //     $message->from('settlement@razorpay.com', 'Kotak Settlement');

        //     $message->subject('Refund File Processed');

        //     $message->to($emails);

        //     $message->attach($data['refundFile']);
        // });
    }
}
