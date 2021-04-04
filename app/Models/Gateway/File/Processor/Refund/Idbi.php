<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingIdbi\RefundFields;

class Idbi extends Base
{
    use FileHandler;

    const FILE_NAME              = 'IDBI_REFUND';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::IDBI_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_IDBI;
    const GATEWAY_CODE           = IFSC::IBKL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE              = 'IDB';
    const MERCHANT_NAME          = 'RAZORPAY';
    const BASE_STORAGE_DIRECTORY = 'Idbi/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                RefundFields::SR_NO                 => ++$index,
                RefundFields::REFUND_ID             => $row['refund']['id'],
                RefundFields::BANK_ID               => static::BANK_CODE,
                RefundFields::MERCHANT_NAME         => static::MERCHANT_NAME,
                RefundFields::PAYMENT_DATE          => $this->getFormattedDate($row['payment']['created_at']),
                RefundFields::REFUND_DATE           => $this->getFormattedDate($row['refund']['created_at']),
                RefundFields::BANK_MERCHANT_CODE    => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REFERENCE_NUMBER => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                RefundFields::PGI_REFERENCE_NUMBER  => $row['payment']['id'],
                RefundFields::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
                RefundFields::REFUND_REASON         => '',
            ];
        }

        return $formattedData;
    }

    protected function getFormattedDate($date)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format('d-m-y');
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
