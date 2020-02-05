<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Gateway\File\Status;
use RZP\Gateway\Upi\Sbi\RefundFile;
use RZP\Models\Base\PublicCollection;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class UpiSbi extends Base
{
    const FILE_NAME       = 'SBI_UPI';
    const EXTENSION       = FileStore\Format::CSV;
    const FILE_TYPE       = FileStore\Type::SBI_UPI_REFUND;
    const GATEWAY         = Payment\Gateway::UPI_SBI;
    const BEAM_FILE_TYPE  = 'refund';

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

            $formattedData[] = [
                $pgMerchantId  => trim($row['gateway']['gateway_merchant_id'], '"'),
                $refReqNo      => trim($row['refund']['id'], '"'),
                $txnRefNo      => trim($row['gateway']['npci_reference_id'], '"'),
                $custRefNo     => trim($row['gateway']['gateway_payment_id'], '"'),
                $orderNo       => trim($row['payment']['id'], '"'),
                $refAmt        => trim($row['refund']['amount'] / 100, '"'),
                $refRemark     => trim('Refund for ' . $row['payment']['id'], '"'),
            ];
        }

        return $formattedData;
    }

    public function createFile($data)
    {
        $defaultExcelEnclosure = $this->config->get('excel.csv.enclosure');

        $this->config->set('excel.csv.enclosure', '');

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

        $this->config->set('excel.csv.enclosure', $defaultExcelEnclosure);
    }

    public function sendFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $data =  [
            BeamService::BEAM_PUSH_FILES   => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::SBI_UPI_REFUND_FILE_JOB_NAME
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
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dt = Carbon::now(Timezone::IST)->format('dmY_Hi');

        return static::FILE_NAME . '_' . $dt;
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
}
