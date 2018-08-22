<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Isg extends Base
{
    use FileHandler;

    const FILE_NAME_REFUND              = 'Refund';
    const FILE_NAME_SUMMARY             = 'Summary';
    const EXTENSION_REFUND              = FileStore\Format::CSV;
    const EXTENSION_SUMMARY             = FileStore\Format::TXT;
    const FILE_TYPE_REFUND              = FileStore\Type::ISG_REFUND;
    const FILE_TYPE_SUMMARY             = FileStore\Type::ISG_SUMMARY;
    const GATEWAY                       = Payment\Gateway::ISG;

    const REFUND_ID                     = 'RFD_TXN_ID';
    const MERCHANT_PAN                  = 'MERCHANT_PAN';
    const TRANSACTION_DATE              = 'TXN_DATE_TIME';
    const TRANSACTION_AMOUNT            = 'TXN_AMOUNT';
    const REFUND_TRANSACTION_DATE       = 'RFD_TXN_DATE_TIME';
    const REFUND_TRANSACTION_AMOUNT     = 'RFD_TXN_AMOUNT';
    const AUTH_CODE                     = 'AUTH_CODE';
    const RRN                           = 'RRN';

    const REFUND_COLUMN_HEADERS = [
        self::REFUND_ID,
        self::MERCHANT_PAN,
        self::TRANSACTION_DATE,
        self::TRANSACTION_AMOUNT,
        self::REFUND_TRANSACTION_DATE,
        self::REFUND_TRANSACTION_AMOUNT,
        self::AUTH_CODE,
        self::RRN,
    ];

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->findBetweenTimestampsForGateway($begin, $end, self::GATEWAY);

        return $refunds;
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $refundFileData = $this->formatDataForRefundFile($data);

            $refundFileName = $this->getRefundFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(self::EXTENSION_REFUND)
                ->content($refundFileData)
                ->name($refundFileName)
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE_REFUND)
                ->entity($this->gatewayFile)
                ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $summaryFileData = $this->formatDataForSummaryFile(count($data));

            $summaryFileName = $this->getSummaryFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(self::EXTENSION_SUMMARY)
                ->content($summaryFileData)
                ->name($summaryFileName)
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE_SUMMARY)
                ->entity($this->gatewayFile)
                ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }

        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForRefundFile(array $data)
    {
        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'],
                                                       Timezone::IST)
                                                       ->format('YmdHis');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'],
                                                      Timezone::IST)
                                                      ->format('YmdHis');

            $formattedData[] = [
                self::REFUND_ID                     => $row['refund']['id'],
                self::MERCHANT_PAN                  => $row['gateway']['merchant_pan'],
                self::TRANSACTION_DATE              => $paymentDate,
                self::TRANSACTION_AMOUNT            => $this->formatAmount($row['payment']['amount']),
                self::REFUND_TRANSACTION_DATE       => $refundDate,
                self::REFUND_TRANSACTION_AMOUNT     => $this->formatAmount($row['refund']['amount']),
                self::AUTH_CODE                     => $row['gateway']['auth_code'],
                self::RRN                           => $row['gateway']['rrn'],
            ];
        }

        return $formattedData;
    }

    protected function formatDataForSummaryFile($count)
    {
        $data = 'Total Refunds Records : ' . $count;

        return $data;
    }

    protected function formatDataForMail(array $data)
    {
        $refundFile = $this->gatewayFile
                    ->files()
                    ->where(FileStore\Entity::TYPE, static::FILE_TYPE_REFUND)
                    ->first();

        $summaryFile = $this->gatewayFile
                    ->files()
                    ->where(FileStore\Entity::TYPE, static::FILE_TYPE_SUMMARY)
                    ->first();

        $signedUrlRefund = (new FileStore\Accessor)->getSignedUrlOfFile($refundFile);

        $signedUrlSummary = (new FileStore\Accessor)->getSignedUrlOfFile($summaryFile);

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'count'      => count($data),
        ];

        $mailData[] = [
            'file_name'  => $refundFile->getLocation(),
            'signed_url' => $signedUrlRefund,
            'date'       => $today
        ];

        $mailData[] = [
            'file_name'  => $summaryFile->getLocation(),
            'signed_url' => $signedUrlSummary,
            'date'       => $today
        ];

        return $mailData;
    }

    protected function getRefundFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME_REFUND . '_' . $date;
    }

    protected function getSummaryFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME_SUMMARY . '_' . $date;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
