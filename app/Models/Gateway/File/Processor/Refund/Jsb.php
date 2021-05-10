<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\NetbankingJsb\RefundFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Jsb extends Base
{
    use FileHandler;

    const FILE_NAME              = 'razorpayRefund';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::JSB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_JSB;
    const GATEWAY_CODE           = IFSC::JSFB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Jsb/Refund/Netbanking/';

    const HEADERS = RefundFields::REFUND_FIELDS;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Y-m-d H:i:s');

            $formattedData[] = [
                RefundFields::MERCHANT_CODE         => $row['terminal']['gateway_merchant_id'],
                RefundFields::MERCHANT_NAME         => 'RAZORPAY',
                RefundFields::PAYMENT_ID            => $row['payment']['id'],
                RefundFields::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
                RefundFields::CURRENCY              => 'INR',
                RefundFields::BANK_REFERENCE_NUMBER => $this->fetchBankPaymentId($row),
                RefundFields::TRANSACTION_DATE      => $transactionDate,
            ];
        }

        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmYis');

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

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
