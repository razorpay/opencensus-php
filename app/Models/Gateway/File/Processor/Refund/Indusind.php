<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class Indusind extends Base
{
    use FileHandler;

    const FILE_NAME              = 'PGRefundRAZORPAY';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::INDUSIND_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_INDUSIND;
    const GATEWAY_CODE           = IFSC::INDB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Indusind/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                $bankRefNo = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
            }
            else
            {
                $bankRefNo = $row['gateway']['bank_payment_id'];
            }

            $formattedData[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::REFUND             => RefundFileFields::REFUND_MODE,
                RefundFileFields::BANK               => RefundFileFields::BANK_NAME,
                RefundFileFields::REFUND_AMOUNT      => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                RefundFileFields::BANK_REFERENCE_ID  => $bankRefNo
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->isTestMode() === true)
        {
            return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time . $this->mode;
        }

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time;
    }

    public function sendFile($data)
    {
        return;
    }
}
