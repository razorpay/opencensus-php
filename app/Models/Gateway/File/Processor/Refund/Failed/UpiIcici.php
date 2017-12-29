<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Icici\RefundFile;

class UpiIcici extends Base
{
    const GATEWAY                = Payment\Gateway::UPI_ICICI;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'Icici_Upi_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::ICICI_UPI_REFUND;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            if (isset($row['gateway']) === false)
            {
                continue;
            }

            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('Y-m-d');

            $formattedData[] = [
                RefundFile::BANKADJREF         => $row['refund']['id'],
                RefundFile::FLAG               => 'C',
                RefundFile::SHTDAT             => $date,
                RefundFile::ADJAMT             =>  $this->getFormattedAmount($row['refund']['amount']),
                RefundFile::SHSER              => $row['gateway']['gateway_payment_id'],
                RefundFile::SHCRD              => $row['gateway']['vpa'],
                RefundFile::FILENAME           => self::FILE_NAME,
                RefundFile::REASON             => 'NA',
                RefundFile::SPECIFYOTHER       => $row['refund']['id'],
                RefundFile::MERCHANTACCOUNT    => '',
                RefundFile::MERCHANT_IFSC_CODE => '',
            ];
        }
        return $formattedData;
    }
}
