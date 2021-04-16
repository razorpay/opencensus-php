<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class HdfcCybersource extends Base
{
    const GATEWAY                = Payment\Gateway::CYBERSOURCE;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'Hdfc_Cybersource_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::HDFC_CYBERSOURCE_FAILED_REFUND;
    const BASE_STORAGE_DIRECTORY = 'HdfcCybersource/Refund/Failed/';

    const SR_NO                   = 'Sr No';
    const RAZORPAY_REFUND_ID      = 'Razorpay Refund ID';
    const RAZORPAY_TRANSACTION_ID = 'Razorpay TRansaction ID';
    const MID                     = 'MID';
    const TRANSACTION_DATE        = 'Original Transaction date';
    const PAYMENT_AMOUNT          = 'Original Payment Amount';
    const REFUND_AMOUNT           = 'Original Refund Amount';
    const ACQUIRER                =  Payment\Gateway::ACQUIRER_HDFC;

    const CARD_GATEWAY_API_REFUND_SPAN = 15552000;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->fetchFailedCardRefundsToProcessManually(
            $begin,
            $end,
            static::GATEWAY,
            static::ACQUIRER,
            static::CARD_GATEWAY_API_REFUND_SPAN
            );

        return $refunds;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO                   => $index + 1,
                self::RAZORPAY_REFUND_ID      => $row['refund']['id'],
                self::RAZORPAY_TRANSACTION_ID => $row['payment']['id'],
                self::MID                     => $row['terminal']['gateway_terminal_id'],
                self::TRANSACTION_DATE        => $this->getFormattedDate($row['payment']['created_at'], 'd/m/y H:m'),
                self::PAYMENT_AMOUNT          => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
