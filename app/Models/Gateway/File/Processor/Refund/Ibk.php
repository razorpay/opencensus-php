<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Gateway\Mozart\NetbankingIbk\RefundFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Ibk extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Refund_{$date}_IndianBank-NetBanking';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::IBK_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_IBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY_CODE           = [IFSC::IDIB, IFSC::ALLA, Payment\Processor\Netbanking::IDIB_C];
    const BASE_STORAGE_DIRECTORY = 'Ibk/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $i = 1;

        foreach ($data as $row)
        {
            $txnDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('m/d/Y');
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('m/d/Y');

            $content[] = [
                RefundFields::SR_NO              => $i++,
                RefundFields::REFUND_ID          => $row['refund']['id'],
                RefundFields::BANK_ID            => "INB",
                RefundFields::MERCHANT_NAME      => $row['terminal']['gateway_merchant_id'],
                RefundFields::TXN_DATE           => $txnDate,
                RefundFields::REFUND_DATE        => $refundDate,
                RefundFields::BANK_MERHCANT_CODE => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REF_NO        => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                RefundFields::PGI_REF_NO         => $row['payment']['id'],
                RefundFields::TXN_AMOUNT         => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT      => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::BASE_STORAGE_DIRECTORY . strtr(self::FILE_NAME, ['{$date}' => $date]);
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
