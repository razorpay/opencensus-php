<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Isg extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Refund';
    const EXTENSION              = FileStore\Format::CSV;
    const FILE_TYPE              = FileStore\Type::ISG_REFUND;
    const GATEWAY                = Payment\Gateway::ISG;

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

    protected function formatDataForFile(array $data)
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

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME . '_' . $date;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
