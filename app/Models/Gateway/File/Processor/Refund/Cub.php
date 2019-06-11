<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
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

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                RefundFields::BANK_REFERENCE_ID   => $this->fetchBankPaymentId($row['gateway']['raw']),
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

        return static::FILE_NAME . $dateTime;
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
    }
}
