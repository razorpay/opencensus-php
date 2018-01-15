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

    const SR_NO            = 'Sr No';
    const REFUND_ID        = 'refund_id';
    const TRANSACTION_DATE = 'Transaction date';
    const REFUND_DATE      = 'refund Date';
    const PAYMENT_ID       = 'Payment ID';
    const REFUND_AMOUNT    = 'Refund Amount';
    const PAYMENT_AMOUNT   = 'Payment Amount';
    const MERCHANT_CODE    = 'Merchant Code';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('Y/m/d');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'], Timezone::IST)->format('Y/m/d');

            $formattedData[] = [
                self::SR_NO            => $index + 1,
                self::REFUND_ID        => $row['refund']['id'],
                self::TRANSACTION_DATE => $date,
                self::REFUND_DATE      => $refundDate,
                self::PAYMENT_ID       => $row['payment']['id'],
                self::PAYMENT_AMOUNT   => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT    => $this->getFormattedAmount($row['refund']['amount']),
                self::MERCHANT_CODE    => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;
    }
}
