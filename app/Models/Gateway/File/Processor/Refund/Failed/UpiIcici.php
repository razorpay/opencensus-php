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
    const EXTENSION              = FileStore\Format::CSV;
    const FILE_NAME              = 'Icici_Upi_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::ICICI_UPI_REFUND;
    const BASE_STORAGE_DIRECTORY = 'Icici/Refund/Upi/Failed/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                RefundFile::BANKADJREF         => $row['refund']['id'],
                RefundFile::FLAG               => 'C',
                RefundFile::SHTDAT             => $this->getformattedDate($row['payment']['authorized_at'], 'Y-m-d'),
                RefundFile::ADJAMT             => $this->getFormattedAmount($row['refund']['amount']),
                RefundFile::SHSER              => $row['gateway']['gateway_payment_id'],
                RefundFile::SHCRD              => $row['payment']['vpa'],
                RefundFile::FILENAME           => self::FILE_NAME,
                RefundFile::REASON             => 'NA',
                RefundFile::SPECIFYOTHER       => $row['refund']['id'],
                RefundFile::MERCHANTACCOUNT    => '',
                RefundFile::MERCHANT_IFSC_CODE => '',
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
