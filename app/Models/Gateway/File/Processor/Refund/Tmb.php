<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Tmb extends Base
{
    use FileHandler;

    const FILE_NAME              = 'RazorPay_Tmb_Refund_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::TMB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_TMB;
    const GATEWAY_CODE           = IFSC::TMBL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Tmb/Refund/Netbanking/';

    // Refund fields
    const SERIAL_NO          = 'Sr No';
    const REFUND_ID          = 'Refund ID';
    const BANK_ID            = 'Bank ID';
    const MERCHANT_NAME      = 'Merchant Name';
    const TXN_DATE           = 'Txn Date';
    const REFUND_DATE        = 'Refund Date';
    const MERCHANT_CODE      = 'Merchant Code';
    const BANK_REFERENCE_ID  = 'Bank Reference No';
    const MERCHANT_TRN       = 'Merchant TRN';
    const TRANSACTION_AMOUNT = 'Transaction Amount';
    const REFUND_AMOUNT      = 'Refund Amount';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $formattedData[] = [
            self::SERIAL_NO          => self::SERIAL_NO  ,
            self::REFUND_ID          => self::REFUND_ID,
            self::BANK_ID            => self::BANK_ID,
            self::MERCHANT_NAME      => self::MERCHANT_NAME,
            self::TXN_DATE           => self::TXN_DATE,
            self::REFUND_DATE        => self::REFUND_DATE,
            self::MERCHANT_CODE      => self::MERCHANT_CODE,
            self::BANK_REFERENCE_ID  => self::BANK_REFERENCE_ID,
            self::MERCHANT_TRN       => self::MERCHANT_TRN,
            self::TRANSACTION_AMOUNT => self::TRANSACTION_AMOUNT,
            self::REFUND_AMOUNT      => self::REFUND_AMOUNT
        ];

        foreach ($data as $index => $row)
        {
            $txndate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Y-m-d H:i:s');

            $refunddate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Y-m-d');

            $formattedData[] = [
                self::SERIAL_NO          => $index + 1,
                self::REFUND_ID          => $row['refund']['id'],
                self::BANK_ID            => 'TMB',
                self::MERCHANT_NAME      => $row['merchant']['name'],
                self::TXN_DATE           => $txndate,
                self::REFUND_DATE        => $refunddate,
                self::MERCHANT_CODE      => $row['terminal']['gateway_merchant_id'],
                self::BANK_REFERENCE_ID  => $this->fetchBankPaymentId($row),
                self::MERCHANT_TRN       => $row['payment']['id'],
                self::TRANSACTION_AMOUNT => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                self::REFUND_AMOUNT      => number_format($row['refund']['amount'] / 100, 2, '.', '')
            ];
        }

        $formattedData = $this->getTextData($formattedData, '', ',');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time;
    }

    protected function fetchBankPaymentId($row)
    {
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $row['gateway']['data']['bank_payment_id'];
    }
}
