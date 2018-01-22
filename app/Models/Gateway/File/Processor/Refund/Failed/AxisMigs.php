<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;

class AxisMigs extends Base
{
    const GATEWAY            = Payment\Gateway::AXIS_MIGS;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'Axis_Migs_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::AXIS_MIGS_FAILED_REFUND;

    const SR_NO                  = 'Sr No';
    const VPC_TRANSACTION_NO     = 'vpc_TransactionNo';
    const REFUND_ID              = 'refund_id';
    const REFUND_DATE            = 'refund Date';
    const TRANSACTION_DATE       = 'Transaction date';
    const REFUND_AMOUNT          = 'Refund Amount';
    const PAYMENT_AMOUNT         = 'Payment Amount';
    const REFUND_TYPE            = 'Refund Type';
    const MERCHANT_CODE          = 'Merchant Code';
    const PAYMENT_ID             = 'Payment ID';
    const ACQUIRER               =  Payment\Gateway::ACQUIRER_AXIS;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO                   => $index + 1,
                self::VPC_TRANSACTION_NO      => $row['gateway']['vpc_TransactionNo'],
                self::REFUND_ID               => $row['refund']['id'],
                self::REFUND_DATE             => $this->getFormattedDate($row['refund']['last_attempted_at'], 'Y/m/d'),
                self::TRANSACTION_DATE        => $this->getFormattedDate($row['payment']['created_at'], 'Y/m/d'),
                self::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
                self::PAYMENT_AMOUNT          => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_TYPE             => $row['payment']['refund_status'],
                self::MERCHANT_CODE           => $row['terminal']['gateway_merchant_id'],
                self::PAYMENT_ID              => $row['payment']['id'],
            ];
        }

        return $formattedData;
    }
}
