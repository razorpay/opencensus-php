<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\RefundFields;

class Rbl extends Processor\Base
{
    use GenerateRefundFile;

    const FILE_NAME              = 'Rbl_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::RBL_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_RBL;
    const GATEWAY_CODE           = IFSC::RATN;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    const HEADERS = [
        RefundFields::SERIAL_NO,
        RefundFields::REFUND_ID,
        RefundFields::BANK_ID,
        RefundFields::MERCHANT_NAME,
        RefundFields::TRANSACTION_DATE,
        RefundFields::REFUND_DATE,
        RefundFields::MERCHANT_ID,
        RefundFields::BANK_REFERENCE,
        RefundFields::PGI_REFERENCE,
        RefundFields::TRANSACTION_AMOUNT,
        RefundFields::REFUND_AMOUNT,
    ];

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        $formattedData[] = static::HEADERS;

        foreach ($this->data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row['payment']['created_at'],
                        Timezone::IST)
                        ->format('m-d-y h:m:s');

            $refundDate = Carbon::createFromTimestamp(
                              $row['refund']['created_at'],
                              Timezone::IST)
                              ->format('m-d-y h:m:s');

            $formattedData[] = [
                RefundFields::SERIAL_NO          => $index++,
                RefundFields::REFUND_ID          => $row['refund']['id'],
                RefundFields::BANK_ID            => Constants::BANK_ID,
                RefundFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                RefundFields::TRANSACTION_DATE   => $date,
                RefundFields::REFUND_DATE        => $refundDate,
                RefundFields::MERCHANT_ID        => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REFERENCE     => $row['gateway']['bank_payment_id'],
                RefundFields::PGI_REFERENCE      => $row['payment']['id'],
                RefundFields::TRANSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT      => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
