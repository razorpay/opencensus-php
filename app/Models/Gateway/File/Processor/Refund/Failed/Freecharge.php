<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;

class Freecharge extends Base
{
    const GATEWAY            = Payment\Gateway::WALLET_FREECHARGE;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'Freecharge_Wallet_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::FREECHARGE_WALLET_REFUND;

    const SL_NO              = 'Sr No';
    const REFUND_ID          = 'Refund ID';
    const REFUND_DATE        = 'Refund Date';
    const REFUND_AMOUNT      = 'Refund Amount';
    const PAYMENT_ID         = 'Payment ID';
    const TRANSACTION_DATE   = 'Transaction date';
    const PAYMENT_AMOUNT     = 'Payment Amount';
    const REFUND_TYPE        = 'Refund Type';
    const GATEWAY_REFERENCE  = 'Gateway reference';
    const MERCHANT_ID        = 'Merchant ID';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SL_NO             => $index + 1,
                self::REFUND_ID         => $row['refund']['id'],
                self::REFUND_DATE       => $this->formattedDate($row['refund']['id'], 'd/m/Y'),
                self::REFUND_AMOUNT     => $this->getFormattedAmount($row['refund']['amount']),
                self::PAYMENT_ID        => $row['payment']['id'],
                self::TRANSACTION_DATE  => $this->formattedDate($row['payment']['id'], 'd/m/Y'),
                self::PAYMENT_AMOUNT    => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_TYPE       => $row['payment']['refund_status'],
                self::GATEWAY_REFERENCE => $row['gateway']['gateway_payment_id'],
                self::MERCHANT_ID       => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $formattedData;
    }
}
