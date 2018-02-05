<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;

class Payzapp extends Base
{
    const GATEWAY            = Payment\Gateway::WALLET_PAYZAPP;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'Payzapp_Wallet_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::PAYZAPP_WALLET_FAILED_REFUND;

    const SR_NO              = 'Sr No';
    const TRANSACTION_DATE   = 'Transaction date';
    const GATEWAY_REFERENCE  = 'Gateway reference';
    const PAYMENT_ID         = 'Payment ID';
    const PAYMENT_AMOUNT     = 'Payment Amount';
    const REFUND_AMOUNT      = 'Refund Amount';
    const MERCHANT_CODE      = 'Merchant Code';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO             => $index + 1,
                self::TRANSACTION_DATE  => $this->getFormattedDate($row['payment']['created_at'], 'D/M/Y'),
                self::GATEWAY_REFERENCE => $row['gateway']['gateway_payment_id'],
                self::PAYMENT_ID        => $row['payment']['id'],
                self::PAYMENT_AMOUNT    => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT     => $this->getFormattedAmount($row['refund']['amount']),
                self::MERCHANT_CODE     => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;
    }
}
