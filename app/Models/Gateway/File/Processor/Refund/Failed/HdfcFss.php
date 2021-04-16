<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class HdfcFss extends Base
{
    const GATEWAY                = Payment\Gateway::HDFC;
    const ACQUIRER               = Payment\Gateway::ACQUIRER_HDFC;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'Hdfc_FSS_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::HDFC_FSS_FAILED_REFUND;
    const BASE_STORAGE_DIRECTORY = 'HdfcFss/Refund/Failed/';

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

    const CARD_GATEWAY_API_REFUND_SPAN = 15552000;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->fetchFailedCardRefundsToProcessManually(
            $begin,
            $end,
            static::GATEWAY,
            static::ACQUIRER,
            static::CARD_GATEWAY_API_REFUND_SPAN
            );

        return $refunds;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO              => $index + 1,
                // Mecode needs to populated manually from the sheet
                self::MECODE             => '',
                self::TERMINAL_ID        => $row['terminal']['gateway_terminal_id'],
                self::CARD_NUMBER        => $this->getCardNumber($row['card']['iin'], $row['card']['last4']),
                self::TRANSACTION_DATE   => $this->getFormattedDate($row['payment']['created_at'], 'd/m/Y'),
                self::TARNSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_ID          => $row['refund']['id'],
                self::PAYMENT_ID         => $row['payment']['id'],
                self::APPROVAL_CODE      => $row['payment']['reference2'],
                self::REFUND_AMOUNT     => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
