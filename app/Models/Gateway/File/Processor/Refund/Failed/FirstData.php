<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class FirstData extends Base
{
    const GATEWAY          = Payment\Gateway::FIRST_DATA;
    const EXTENSION        = FileStore\Format::XLSX;
    const FILE_NAME        = 'FirstData_Failed_Refunds';
    const FILE_TYPE        = FileStore\Type::FIRSTDATA_FAILED_REFUND;

    const SR_NO                  = 'Sr No';
    const GATEWAY_TRANSACTION_ID =  'Gateway Transaction ID';
    const REFUND_ID              = 'refund_id';
    const REFUND_TYPE            = 'Refund Type';
    const TRANSACTION_DATE       = 'Transaction date';
    const REFUND_DATE            = 'refund Date';
    const PAYMENT_ID             = 'Payment ID';
    const REFUND_AMOUNT          = 'Refund Amount';
    const PAYMENT_AMOUNT         = 'Payment Amount';
    const MERCHANT_CODE          = 'Merchant Code';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $transactionDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('Y/m/d');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['last_attempted_at'], Timezone::IST)->format('Y/m/d');

            $formattedData[] = [
                self::SR_NO                  => $index + 1,
                self::GATEWAY_TRANSACTION_ID => $row['gateway']['gateway_transaction_id'],
                self::REFUND_ID              => $row['refund']['id'],
                self::REFUND_TYPE            => $row['payment']['refund_status'],
                self::TRANSACTION_DATE       => $transactionDate,
                self::REFUND_DATE            => $refundDate,
                self::PAYMENT_ID             => $row['payment']['id'],
                self::PAYMENT_AMOUNT         => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT          => $this->getFormattedAmount($row['refund']['amount']),
                self::MERCHANT_CODE          => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;
    }
}
