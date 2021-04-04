<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingCub\RefundFields;

class Cub extends Base
{
    use FileHandler;

    // todo : refund file name
    const FILE_NAME              = 'CUB REFUND';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::CUB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CUB;
    const GATEWAY_CODE           = IFSC::CIUB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Cub/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                RefundFields::BANK_REFERENCE_ID   => $this->fetchBankPaymentId($row),
                RefundFields::PAYMENT_ID          => $row['payment']['id'],
                RefundFields::TRANSACTION_AMOUNT  => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                RefundFields::TRANSACTION_DATE    => $date,
                RefundFields::REFUND_AMOUNT       => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                RefundFields::TYPE_IDENTIFICATION => self::REFUND_TYPE,
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['data']['bank_payment_id'];
    }
}
