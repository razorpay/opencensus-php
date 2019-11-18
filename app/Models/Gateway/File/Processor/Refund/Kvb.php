<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Mozart\NetbankingKvb\RefundFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Kvb extends Base
{
    use FileHandler;

    const FILE_NAME                  = '_REFUND_';
    const SERIAL_NO                  = '_01';
    const EXTENSION                  = FileStore\Format::XLSX;
    const FILE_TYPE                  = FileStore\Type::KVB_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_KVB;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::KVBL;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $count = 1;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'],Timezone::IST)->format('d/m/Y');

            $content[] = [
                RefundFields::SR_NO                 => $count++,
                RefundFields::TRANSACTION_DATE      => $transactionDate,
                RefundFields::REFUND_DATE           => $refundDate,
                RefundFields::BANK_REFERENCE_NUMBER => $row['gateway']['data']['bank_payment_id'],
                RefundFields::PGI_REFERENCE_NO      => $row['payment']['id'],
                RefundFields::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
                RefundFields::ACCOUNT_NUMBER        => $row['gateway']['data']['account_number'],
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return 'RAZORPAY'. self::FILE_NAME . $date . '_' . '01';
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
