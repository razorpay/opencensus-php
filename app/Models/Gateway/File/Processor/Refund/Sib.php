<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Sib extends Base
{
    use FileHandler;

    const FILE_NAME              = 'RazorPay_SIB_RefundtoSIB_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::SIB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_SIB;
    const GATEWAY_CODE           = IFSC::SIBL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Sib/Refund/Netbanking/';

    // Refund fields
    const SERIAL_NO          = 'Sr No';
    const BANK_REFERENCE_ID  = 'Bank Reference No.';
    const REFUND_AMOUNT      = 'Refund Amount';
    const PAYMENT_ID         = 'Payment Id';
    const REFUND_MODE        = 'REFUND';
    const PAYEE_ID           = 'Payee Id';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SERIAL_NO         => $index + 1,
                self::PAYMENT_ID        => $row['payment']['id'],
                self::REFUND_MODE       => self::REFUND_MODE,
                self::PAYEE_ID          => $row['terminal']['gateway_merchant_id'],
                self::REFUND_AMOUNT     => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                self::BANK_REFERENCE_ID => $this->fetchBankPaymentId($row)
            ];
        }

        $formattedData = $this->getTextData($formattedData, '', '||');

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
