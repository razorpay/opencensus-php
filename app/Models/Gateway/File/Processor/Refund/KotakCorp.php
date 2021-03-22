<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class KotakCorp extends Base
{
    use FileHandler;

    const GATEWAY_TXN_ID      = 'TRANSACTION_ID';
    const REFUND_TYPE         = 'REFUND_TYPE';
    const REFUND_AMOUNT       = 'AMT_FOR_REFUND';
    const BANK_REFERENCE_ID   = 'BANK_TXN_ID';
    const TXN_DATE            = 'TXN_DATE';
    const TXN_AMT             = 'TXN_AMT';
    const VERIFICATION_ID     = 'CANC_TXN_ID';
    const AGGREGATOR_ID       = 'ENTITY_CODE';

    const FILE_NAME                  = 'KOTAK_CORP_REFUND';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::KOTAK_CORP_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_KOTAK;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = Payment\Processor\Netbanking::KKBK_C;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Ymd');

            $content[] = [
                self::GATEWAY_TXN_ID       => $row['gateway'][Netbanking::VERIFICATION_ID],
                self::REFUND_TYPE          => 'R',
                self::REFUND_AMOUNT        => $this->getFormattedAmount($row[Entity::REFUND][Payment\Refund\Entity::AMOUNT]),
                self::BANK_REFERENCE_ID    => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                self::TXN_DATE             => $date,
                self::TXN_AMT              => $this->getFormattedAmount($row[Entity::PAYMENT][Payment\Entity::AMOUNT]),
                self::VERIFICATION_ID      => $row['gateway'][Netbanking::VERIFICATION_ID],
                self::AGGREGATOR_ID        => $row['terminal']['gateway_merchant_id2'],
            ];
        }
         $content = $this->getTextData($content, '', '|');

        return $content;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }
}
