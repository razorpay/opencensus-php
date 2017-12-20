<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class AirtelMoney extends Base
{
    use FileHandler;

    const GATEWAY                = Payment\Gateway::WALLET_AIRTELMONEY;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'airtel_money_failed_refunds';
    const FILE_TYPE              = FileStore\Type::AIRTELMONEY_WALLET_REFUND;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST
                )
                ->format('Y-d-m');

            $formattedData[] = [
                'Refund ID'     => $row['refund']['id'],
                'Refund Date'   => $refundDate,
                'payment ID'    => $row['gateway']['payment_id'],
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100,
            ];
        }
        return $formattedData;
    }

}
