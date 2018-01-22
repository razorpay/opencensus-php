<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;

class HdfcFss extends Base
{
    const GATEWAY            = Payment\Gateway::HDFC;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'FSS_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::FSS_FAILED_REFUND;

    const SR_NO            = 'Sr No';
    const REFUND_ID        = 'refund_id';
    const TRANSACTION_DATE = 'Transaction date';
    const REFUND_DATE      = 'refund Date';
    const PAYMENT_ID       = 'Payment ID';
    const REFUND_AMOUNT    = 'Refund Amount';
    const PAYMENT_AMOUNT   = 'Payment Amount';
    const MERCHANT_CODE    = 'Merchant Code';
    const ACQUIRER         =  Payment\Gateway::ACQUIRER_HDFC;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO            => $index + 1,
                self::REFUND_ID        => $row['refund']['id'],
                self::REFUND_DATE      => $this->getFormattedDate($row['refund']['last_attempted_at'], 'Y/m/d'),
                self::TRANSACTION_DATE => $this->getFormattedDate($row['payment']['created_at'], 'Y/m/d'),
                self::PAYMENT_ID       => $row['payment']['id'],
                self::PAYMENT_AMOUNT   => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT    => $this->getFormattedAmount($row['refund']['amount']),
                self::MERCHANT_CODE    => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;
    }
}
