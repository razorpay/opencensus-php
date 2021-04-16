<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class AirtelMoney extends Base
{
    const GATEWAY                = Payment\Gateway::WALLET_AIRTELMONEY;
    const EXTENSION              = FileStore\Format::CSV;
    const FILE_NAME              = 'Airtelmoney_Wallet_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::AIRTELMONEY_WALLET_FAILED_REFUND;
    const BASE_STORAGE_DIRECTORY = 'AirtelMoney/Refund/Wallet/Failed/';

    const SR_NO               = 'Sr No';
    const CUSTOMER_PHONE      = 'Customer Phone';
    const TRANSACTION_DATE    = 'Transaction date';
    const PAYMENT_AMOUNT      = 'Payment Amount';
    const REFUND_AMOUNT       = 'Refund Amount';
    const REFUND_TYPE         = 'Refund Type';
    const AIRTEL_MONEY_ID     = 'Airtelmoney_id';
    const RAZORPAY_PAYMENT_ID = 'Payment ID';
    const REFUND_ID           = 'Refund ID';
    const MERCHANT_CODE       = 'Merchant Code';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO               => $index + 1,
                self::CUSTOMER_PHONE      => $row['gateway']['contact'],
                self::TRANSACTION_DATE    => $this->getFormattedDate($row['payment']['created_at'], 'd/m/Y'),
                self::PAYMENT_AMOUNT      => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT       => $this->getFormattedAmount($row['refund']['amount']),
                self::REFUND_TYPE         => $row['payment']['refund_status'],
                self::AIRTEL_MONEY_ID     => $row['gateway']['gateway_payment_id'],
                self::RAZORPAY_PAYMENT_ID => $row['payment']['id'],
                self::REFUND_ID           => $row['refund']['id'],
                self::MERCHANT_CODE       => $row['gateway']['gateway_merchant_id']
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
