<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class AirtelMoney extends Base
{
    const GATEWAY            = Payment\Gateway::WALLET_AIRTELMONEY;
    const EXTENSION          = FileStore\Format::XLSX;
    const FILE_NAME          = 'Airtelmoney_Wallet_Failed_Refunds';
    const FILE_TYPE          = FileStore\Type::AIRTELMONEY_WALLET_REFUND;

    const SR_NO              = 'Sr No';
    const TRANSACTION_DATE   = 'Transaction date';
    const GATEWAY_REFERENCE  = 'Gateway reference';
    const ORDER              = 'Order #';
    const ORDER_AMOUNT       = 'Order Amount';
    const REFUND_AMOUNT      = 'Refund Amount';
    const MERCHANT_CODE      = 'Merchant Code';

    protected function formatDataForFile(array $data)
    {
        $i = 1;

        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d/m/Y');

            $i++;

            $formattedData[] = [
                self::SR_NO             => $i,
                self::TRANSACTION_DATE  => $date,
                self::GATEWAY_REFERENCE => $row['gateway']['gateway_payment_id'],
                self::ORDER             => $row['payment']['id'],
                self::REFUND_AMOUNT     => $row['payment']['amount'] / 100,
                self::REFUND_AMOUNT     => $row['refund']['amount'] / 100,
                self::MERCHANT_CODE     => $row['terminal']['gateway_merchant_id']
            ];
        }

        return $formattedData;

    }
}
