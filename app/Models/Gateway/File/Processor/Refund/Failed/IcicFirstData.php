<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;

class IcicFirstData extends Base
{
    const GATEWAY          = Payment\Gateway::FIRST_DATA;
    const EXTENSION        = FileStore\Format::XLSX;
    const FILE_NAME        = 'FirstData_Failed_Refunds';
    const FILE_TYPE        = FileStore\Type::FIRSTDATA_FAILED_REFUND;

    const SR_NO                   = 'Sr No';
    const MERCHANT_TRANSACTION_ID = 'Merchant Transaction ID';
    const REFUND_TYPE             = 'Refund Type';
    const TRANSACTION_DATE        = 'Transaction date';
    const REFUND_DATE             = 'refund Date';
    const ORDER_ID                = 'Order ID';
    const REFUND_AMOUNT           = 'Refund Amount';
    const PAYMENT_AMOUNT          = 'Payment Amount';
    const STORE_ID                = 'Store ID';
    const ACQUIRER                =  Payment\Gateway::ACQUIRER_ICIC;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO                   => $index + 1,
                self::MERCHANT_TRANSACTION_ID => $row['refund']['id'],
                self::REFUND_TYPE             => $row['payment']['refund_status'],
                self::REFUND_DATE             => $this->getFormattedDate($row['refund']['last_attempted_at'], 'Y/m/d'),
                self::TRANSACTION_DATE        => $this->getFormattedDate($row['payment']['created_at'], 'Y/m/d'),
                self::ORDER_ID                => $row['payment']['id'],
                self::PAYMENT_AMOUNT          => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
                self::STORE_ID                => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;
    }
}
