<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\NetbankingScb\RefundFields;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Services\NbPlus\Netbanking;

class Scbl extends Base
{
    use FileHandler;

    const FILE_NAME                  = '_REFUND_';
    const SERIAL_NO                  = '_01';
    const EXTENSION                  = FileStore\Format::XLSX;
    const FILE_TYPE                  = FileStore\Type::SCB_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_SCB;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::SCBL;
    const BASE_STORAGE_DIRECTORY     = 'Scbl/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $count = 1;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-m-Y');
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'],Timezone::IST)->format('d-m-Y');

            $content[] = [
                RefundFields::SR_NO                 => $count++,
                RefundFields::TRANSACTION_DATE      => $transactionDate,
                RefundFields::REFUND_DATE           => $refundDate,
                RefundFields::BANK_REFERENCE_NUMBER => $this->fetchBankPaymentId($row),
                RefundFields::PAYMENT_ID            => $row['payment']['id'],
                RefundFields::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        return self::BASE_STORAGE_DIRECTORY . 'RAZORPAY'. self::FILE_NAME . $date ;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID];
        }

        return $data['gateway']['data']['bank_payment_id'];
    }
}
