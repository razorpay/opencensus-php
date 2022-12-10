<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\File\Status;
use RZP\Gateway\Upi\Sbi\RefundFile;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class UpiSbi extends Base
{
    const FILE_NAME       = 'SBI0000000000232';
    const EXTENSION       = FileStore\Format::CSV;
    const FILE_TYPE       = FileStore\Type::SBI_UPI_REFUND;
    const GATEWAY         = Payment\Gateway::UPI_SBI;
    const BEAM_FILE_TYPE  = 'refund';

    const BASE_STORAGE_DIRECTORY = 'upi/upi_sbi/refund/normal_refund_file/';

    /**
     * @param int $begin
     * @param int $end
     * @return PublicCollection
     */
    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        //
        // Regular flow - fetching refunds from API DB
        //

        $refunds = $this->repo->refund->findBetweenTimestampsForGateway($begin, $end, static::GATEWAY);

        return $refunds;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => static::GATEWAY,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $pgMerchantId = trim(RefundFile::PG_MERCHANT_ID, '"');
            $refReqNo     = trim(RefundFile::REFUND_REQ_NO, '"');
            $txnRefNo     = trim(RefundFile::TRANS_REF_NO, '"');
            $custRefNo    = trim(RefundFile::CUSTOMER_REF_NO, '"');
            $orderNo      = trim(RefundFile::ORDER_NO, '"');
            $refAmt       = trim(RefundFile::REFUND_REQ_AMT, '"');
            $refRemark    = trim(RefundFile::REFUND_REMARK, '"');

            $referenceNo = $row['gateway']['gateway_data']['addInfo2'] ?? '';

            $paymentId = $row['payment']['id'];

            // if it is an unexpected payment, map the merchant_reference to the paymentid.
            // This the paymentid generated at the banks end and will be used for refunding.

            if ((isset($row['gateway']['merchant_reference']) === true) and
                (empty($row['gateway']['merchant_reference']) === false ))
            {
                $paymentId = $row['gateway']['merchant_reference'];
            }

            $gatewayAmt = null;

            if (isset($row['refund']['gateway_amount']) === true)
            {
                $gatewayAmt = $row['refund']['gateway_amount'];
            }

            // skipping this row if gatewayAmount is 0
            // as we are marking such refund as processed
            if ($gatewayAmt === 0)
            {
                continue;
            }

            $formattedData[] = [
                $pgMerchantId  => trim($row['gateway']['gateway_merchant_id'] ?? '', '"'),
                $refReqNo      => trim($row['refund']['id'], '"'),
                $txnRefNo      => trim($referenceNo, '"'),
                $custRefNo     => trim($row['gateway']['npci_reference_id'] ?? $row['gateway']['customer_reference'] ?? '', '"'),
                $orderNo       => trim($paymentId, '"'),
                $refAmt        => empty($gatewayAmt) ? trim($row['refund']['amount'] / 100, '"') : ($gatewayAmt / 100),
                $refRemark     => trim('Refund for ' . $row['payment']['id'], '"'),
            ];
        }

        return $formattedData;
    }

    public function isUpsRefundGateway()
    {
        return true;
    }

    public function createFile($data)
    {
        $defaultExcelEnclosure = $this->config->get('excel.exports.csv.enclosure');

        $this->config->set('excel.exports.csv.enclosure', '');

        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata)
                    ->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new Exception\GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'      => $this->gatewayFile->getId(),
                    'message' => $e->getMessage(),
                ],
                $e);
        }

        $this->config->set('excel.exports.csv.enclosure', $defaultExcelEnclosure);
    }

    public function sendFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data =  [
            BeamService::BEAM_PUSH_FILES            => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME          => BeamConstants::SBI_UPI_REFUND_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME      => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION    => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [600, 1800, 3600];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'refunds',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'UPI SBI Refund File Send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::REFUNDS]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);

        try
        {
            $this->sendConfirmationMail();
        }
        catch (\Throwable $e)
        {
           $this->trace->traceException($e,
               Trace::ERROR,
               TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
               [
                   'file_name' => $fullFileName,
               ]);
        }
    }

    protected function sendConfirmationMail()
    {
        $file = $this->gatewayFile
                    ->files()
                    ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                    ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => $file->getLocation(),
            'signed_url' => $signedUrl
        ];

        $recipients =['finances.recon@razorpay.com'];

        $refundFileMail = new RefundFileMail($mailData, static::GATEWAY, $recipients);

        Mail::queue($refundFileMail);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dt = Carbon::now(Timezone::IST)->format('dmY_Hi');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $dt;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    /**
     * Function to increase system limits for kubernetes pod.
     * This is done to fix the gateway file refund issue due to huge volume of refunds.
     */
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('8192M');

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }
}
