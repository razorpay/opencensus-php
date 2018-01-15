<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Cybersource extends Base
{
    const GATEWAY          = Payment\Gateway::CYBERSOURCE;
    const EXTENSION        = FileStore\Format::XLSX;
    const FILE_NAME        = 'Cybersource_Failed_Refunds';
    const FILE_TYPE        = FileStore\Type::CYBERSOURCE_FAILED_REFUND;

    const SR_NO            = 'Sr No';
    const CYBERSOURCE_REF  = 'Cybersource Reference Number';
    const REFUND_ID        = 'refund_id';
    const REFUND_AMOUNT    = 'Refund Amount';
    const PAYMENT_AMOUNT   = 'Payment Amount';
    const REFUND_TYPE      = 'Refund Type';
    const MERCHANT_CODE    = 'Merchant Code';
    const TRANSACTION_DATE = 'Transaction date';
    const REFUND_DATE      = 'refund Date';
    const PAYMENT_ID       = 'Payment ID';


    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
           $transactionDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('Y/m/d');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'], Timezone::IST)->format('Y/m/d');

            $formattedData[] = [
                self::SR_NO             => $index + 1,
                self::REFUND_ID         => $row['refund']['id'],
                self::CYBERSOURCE_REF    => $row['gateway']['ref'],
                self::PAYMENT_AMOUNT    => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT     => $this->getFormattedAmount($row['refund']['amount']),
                self::REFUND_TYPE       => $row['payment']['refund_status'],
                self::MERCHANT_CODE     => $row['terminal']['gateway_merchant_id'],
                self::TRANSACTION_DATE  => $transactionDate,
                self::REFUND_DATE       => $refundDate,
                self::PAYMENT_ID        => $row['payment']['id'],
            ];
        }

        return $formattedData;

    }
}
