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

class UpiSbi extends Base
{
    const FILE_NAME       = 'SBI_UPI';
    const EXTENSION       = FileStore\Format::CSV;
    const FILE_TYPE       = FileStore\Type::SBI_UPI_REFUND;
    const GATEWAY         = Payment\Gateway::UPI_SBI;
    const BEAM_FILE_TYPE  = 'refund';

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->findBetweenTimestampsForGateway($begin, $end, static::GATEWAY);

        return $refunds;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                RefundFile::PG_MERCHANT_ID  => $row['gateway']['gateway_merchant_id'],
                RefundFile::REFUND_REQ_NO   => $row['refund']['id'],
                RefundFile::TRANS_REF_NO    => $row['gateway']['npci_reference_id'],
                RefundFile::CUSTOMER_REF_NO => $row['gateway']['gateway_payment_id'],
                RefundFile::ORDER_NO        => $row['payment']['id'],
                RefundFile::REFUND_REQ_AMT  => $row['refund']['amount'] / 100,
                RefundFile::REFUND_REMARK   => 'Refund for ' . $row['payment']['id'],
            ];
        }

        return $formattedData;
    }

    public function createFile($data)
    {
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
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'File Send failure',
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
