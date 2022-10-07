<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Karb extends Base
{
    use FileHandler;

    const SERIAL_NO                  = 'SerialNo';
    const PAYMENT_ID                 = 'TxnId';
    const REFUND                     = 'RFND';
    const BANK_NAME                  = 'Bank Name';
    const REFUND_AMOUNT              = 'Transaction Amount';
    const BANK_REFERENCE_NO          = 'BankRefNo';

    const FILE_NAME                  = 'RAZORPAY_REFUND_PG_';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::KARNATAKA_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_KARNATAKA;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::KARB;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        foreach ($data as $index => $row)
        {
            $content[] = [
                self::SERIAL_NO                 => $index + 1,
                self::PAYMENT_ID                => $row['payment']['id'],
                self::REFUND                    => self::REFUND,
                self::BANK_NAME                 => 'KBL',
                self::REFUND_AMOUNT             => $this->getFormattedAmount($row['refund']['amount']),
                self::BANK_REFERENCE_NO         => $this->fetchBankPaymentId($row)
            ];

        }

        $content = $this->getTextData($content, 'SerialNo||TxnId||RFND||Bank Name||Transaction Amount||BankRefNo||'.PHP_EOL, '||');

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
        return $row['gateway']['bank_transaction_id']; // payment always through nbplus service
    }
}
