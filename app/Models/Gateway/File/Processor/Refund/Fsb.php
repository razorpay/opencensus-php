<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Fsb extends Base
{
    use FileHandler;

    const SR                = 'Sr.No.';
    const REFUNDID          = 'Refund ID';
    const BANKID            = 'Bank ID';
    const MERCHNAME         = 'Merchant Name';
    const TXNDATE           = 'Txn Date';
    const REFUNDDATE        = 'Refund Date';
    const BANKMERCHCODE     = 'Bank Merchant Code';
    const BANKREFNO         = 'Bank Ref No.';
    const PGIREFNO          = 'PGI Reference No.';
    const TXNAMT            = 'Txn Amount';
    const REFUNDAMT         = 'Refund Amount';
    const BANKACCNO         = 'Bank Account No';
    const BANKPAYTYPE       = 'Bank Pay Type';

    const FILE_NAME              = 'refund';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::FSB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_FSB;
    const GATEWAY_CODE           = IFSC::FSFB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Fsfb/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $count = 1;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Ymd');
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('Ymd');

            $formattedData[] = [
                self::SR                    => $count++,
                self::REFUNDID              => $row['refund']['id'],
                self::BANKID                => 'FNC',
                self::MERCHNAME             => $row['merchant']['billing_label'],
                self::TXNDATE               => $transactionDate,
                self::REFUNDDATE            => $refundDate,
                self::BANKMERCHCODE         => $row['terminal']['gateway_merchant_id'],
                self::BANKREFNO             => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                self::PGIREFNO              => $row['payment']['id'],
                self::TXNAMT                => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                self::REFUNDAMT             => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                self::BANKACCNO             => $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER],
                self::BANKPAYTYPE           => "CITNEFT"
            ];
        }
        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time . '-0';
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }
}
