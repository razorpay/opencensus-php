<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Srcb extends Base
{
    use FileHandler;


    const PAYMENT_ID            = 'PG Referenc Number';
    const BANK_REFERENCE_NUMBER = 'Bank Adj Reference Number';
    const REFUND_AMOUNT         = 'Transaction Amount';
    const REFUND_NARRATION      = 'Refund & narration';
    const REFUND_DATE           = 'entrydate(YYYYMMDD)';

    const FILE_NAME                  = 'RAZORPAY_REFUND_PG_';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::SARASWAT_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_SARASWAT;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::SRCB;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        foreach ($data as $row)
        {
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('Ymd');

            $content[] = [
                self::PAYMENT_ID                => $row['payment']['id'],
                self::BANK_REFERENCE_NUMBER     => $this->fetchBankPaymentId($row),
                self::REFUND_AMOUNT             => $this->getFormattedAmount($row['refund']['amount']),
                self::REFUND_NARRATION          => "REFUND",
                self::REFUND_DATE               => $refundDate,
            ];
        }

        $content = $this->getTextData($content, '', '|');

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

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

    protected function fetchBankPaymentId($row)
    {
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $row['gateway']['data']['bank_payment_id'];
    }
}
