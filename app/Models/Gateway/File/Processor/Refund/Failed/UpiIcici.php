<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class UpiIcici extends Base
{
    const GATEWAY                = Payment\Gateway::UPI_ICICI;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'UPI_ICICI_failed_refunds';
    const FILE_TYPE              = FileStore\Type::ICICI_UPI_REFUND;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];
        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST
            )
            ->format('Y-d-m');

            $formattedData[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'payment ID'    => $row['gateway']['payment_id'],
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100,
            ];
        }

        return $formattedData;
    }
}
