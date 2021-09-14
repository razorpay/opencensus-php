<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Bdbl extends Base
{
    use FileHandler;

    const SR_NO                 = 'Sr.No';
    const REFUND_ID             = 'RefundID';
    const BANK_ID               = 'BankId';
    const MERCHANT_NAME         = 'MerchantName';
    const TXN_DATE              = 'TransactionDate';
    const REFUND_DATE           = 'RefundDate';
    const BANK_MERCHANT_CODE    = 'BankMerchantCode';
    const BANK_REFERENCE_NUMBER = 'BankRefNo.';
    const PGI_REFERENCE_NO      = 'PGIReferenceNo.';
    const TXN_AMT               = 'TransactionAmount(Rs)';
    const REFUND_AMOUNT         = 'RefundAmount(Rs)';
    const BANK_ACCOUNT_NUMBER   = 'BankAccountNo.';
    const BANK_PAY_TYPE         = 'BankPayType';

    const FILE_NAME                  = 'Batch_BDN_Refund_';
    const EXTENSION                  = FileStore\Format::CSV;
    const FILE_TYPE                  = FileStore\Type::BDBL_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_BDBL;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::BDBL;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $count = 1;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $bankRefNo = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service

            $bankAccountNo = $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];

            $content[] = [
                self::SR_NO                 => $count++,
                self::REFUND_ID             => $row['refund']['id'],
                self::BANK_ID               => 'BDN',
                self::MERCHANT_NAME         => $row['merchant']['name'],
                self::TXN_DATE              => $transactionDate,
                self::REFUND_DATE           => $refundDate,
                self::BANK_MERCHANT_CODE    => $row['payment']['merchant_id'],
                self::BANK_REFERENCE_NUMBER => $bankRefNo,
                self::PGI_REFERENCE_NO      => $row['payment']['id'],
                self::TXN_AMT               => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
                self::BANK_ACCOUNT_NUMBER   => $bankAccountNo,
                self::BANK_PAY_TYPE         => 'CITNEFT',
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        return self::FILE_NAME . $date;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
