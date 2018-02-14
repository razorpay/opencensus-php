<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;

class IcicFirstData extends Base
{
    const GATEWAY          = Payment\Gateway::FIRST_DATA;
    const EXTENSION        = FileStore\Format::XLSX;
    const ACQUIRER         =  Payment\Gateway::ACQUIRER_ICIC;

    const FILE_NAME        = 'Icic_FirstData_Failed_Refunds';
    const FILE_TYPE        = FileStore\Type::ICIC_FIRST_DATA_FAILED_REFUND;

    const SR_NO                   = 'Sr No';
    const MERCHANT_TRANSACTION_ID = 'Merchant Transaction ID';
    const REFUND_TYPE             = 'Refund Type';
    const TRANSACTION_DATE        = 'Transaction date';
    const REFUND_DATE             = 'refund Date';
    const ORDER_ID                = 'Order ID';
    const REFUND_AMOUNT           = 'Refund Amount';
    const PAYMENT_AMOUNT          = 'Payment Amount';
    const STORE_ID                = 'Store ID';
    const AUTH_CODE               = 'Auth Code';
    const LAST_FOUR_CARD_NUM      = 'Card Number Last Four';

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
                self::SR_NO                   => $index + 1,
                self::MERCHANT_TRANSACTION_ID => $row['refund']['id'],
                self::REFUND_TYPE             => $row['payment']['refund_status'],
                self::REFUND_DATE             => $this->getFormattedDate($row['refund']['last_attempted_at'], 'Y/m/d'),
                self::TRANSACTION_DATE        => $this->getFormattedDate($row['payment']['created_at'], 'Y/m/d'),
                self::ORDER_ID                => $row['payment']['id'],
                self::PAYMENT_AMOUNT          => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
                self::STORE_ID                => $row['terminal']['gateway_merchant_id'],
                self::AUTH_CODE               => $row['gateway']['auth_code'],
                self::LAST_FOUR_CARD_NUM      => $row['card']['last4'],
            ];
        }

        return $formattedData;
    }
}
