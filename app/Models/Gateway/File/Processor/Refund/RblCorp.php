<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\RefundFields;

class RblCorp extends Base
{
    const FILE_NAME              = 'Rbl_Corp_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::RBL_CORP_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_RBL;
    const GATEWAY_CODE           = Payment\Processor\Netbanking::RATN_C;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Rbl/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('m-d-y h:m:s');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('m-d-y h:m:s');

            if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                $bankRefId = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
            }
            else
            {
                $bankRefId = $row['gateway']['bank_payment_id']; // payment through api
            }

            $formattedData[] = [
                RefundFields::SERIAL_NO          => $index++,
                RefundFields::REFUND_ID          => $row['refund']['id'],
                RefundFields::BANK_ID            => Constants::BANK_ID,
                RefundFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                RefundFields::TRANSACTION_DATE   => $date,
                RefundFields::REFUND_DATE        => $refundDate,
                RefundFields::MERCHANT_ID        => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REFERENCE     => $bankRefId,
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

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
