<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class HdfcCybersource extends Base
{
    const GATEWAY          = Payment\Gateway::CYBERSOURCE;
    const EXTENSION        = FileStore\Format::XLSX;
    const FILE_NAME        = 'Cybersource_Failed_Refunds';
    const FILE_TYPE        = FileStore\Type::CYBERSOURCE_FAILED_REFUND;

    const SR_NO                   = 'Sr No';
    const RAZORPAY_REFUND_ID      = 'Razorpay Refund ID';
    const RAZORPAY_TRANSACTION_ID = 'Razorpay TRansaction ID';
    const MID                     = 'MID';
    const TRANSACTION_DATE        = 'Original Transaction date';
    const PAYMENT_AMOUNT          = 'Original Payment Amount';
    const REFUND_AMOUNT           = 'Original Refund Amount';
    const ACQUIRER                =  Payment\Gateway::ACQUIRER_HDFC;

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
}
