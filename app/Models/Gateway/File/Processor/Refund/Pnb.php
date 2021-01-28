<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Pnb\RefundFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Pnb extends Base
{
    use FileHandler;

    // todo : refund file name
    const FILE_NAME              = 'PNB_REFUND';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::PNB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_PNB;
    const GATEWAY_CODE           = [Payment\Processor\Netbanking::PUNB_C, Payment\Processor\Netbanking::PUNB_R, IFSC::ORBC, IFSC::UTBI];
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Ymd');

            $formattedData[] = [
                RefundFields::PAYMENT_ID             => $row['payment']['id'],
                RefundFields::REFUND_OR_CANCELLATION => 'R',
                RefundFields::REFUND_AMOUNT          => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                RefundFields::BANK_PAYMENT_ID        => $row['gateway']['bank_payment_id'],
                RefundFields::DATE                   => $date,
                RefundFields::TRANSACTION_AMOUNT     => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                RefundFields::REFUND_ID              => $row['refund']['id'],
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
}
