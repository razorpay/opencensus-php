<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;

class HdfcFss extends Base
{
    const GATEWAY            = Payment\Gateway::HDFC;
    const ACQUIRER         =  Payment\Gateway::ACQUIRER_HDFC;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'Hdfc_FSS_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::FSS_FAILED_REFUND;

    const SR_NO              = 'Sr No';
    const MECODE             = 'MECODE';
    const TERMINAL_ID        = 'Terminal ID';
    const CARD_NUMBER        = 'Card Number';
    const TRANSACTION_DATE   = 'Transaction date';
    const TARNSACTION_AMOUNT = 'Transaction Amount';
    const REFUND_ID          = 'refund_id';
    const PAYMENT_ID         = 'Payment ID';
    const APPROVAL_CODE      = 'Approval Code';
    const REFUND_AMOUNT      = 'Refund Amount';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO              => $index + 1,
                self::TERMINAL_ID        => $row['terminal']['gateway_terminal_id'],
                self::CARD_NUMBER        => $this->getCardNumber($row['card']['iin'], $row['card']['last4']),
                self::TRANSACTION_DATE   => $this->getFormattedDate($row['payment']['created_at'], 'd/m/Y'),
                self::TARNSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_ID          => $row['refund']['id'],
                self::PAYMENT_ID         => $row['payment']['id'],
                self::APPROVAL_CODE      => $row['payment']['approval_code'],
                self::REFUND_AMOUNT     => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }
}
