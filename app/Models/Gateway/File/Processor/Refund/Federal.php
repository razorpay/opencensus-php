<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Federal\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class Federal extends Base
{
    use FileHandler;

    const FILE_NAME              = 'FBK_REFUND';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::FEDERAL_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_FEDERAL;
    const GATEWAY_CODE           = IFSC::FDRL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    Timezone::IST)
                    ->format('Y-d-m');

            $formattedData[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'PRN'           => $row['payment']['id'],
                'FREEFIELD'     => Constants::FREEFIELD,
                'BID'           => $row['gateway']['bank_payment_id'],
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        return self::FILE_NAME . '_' . $date;
    }
}
