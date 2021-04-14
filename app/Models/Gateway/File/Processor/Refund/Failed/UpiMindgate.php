<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class UpiMindgate extends Base
{
    const GATEWAY                = Payment\Gateway::UPI_MINDGATE;
    const EXTENSION              = FileStore\Format::CSV;
    const FILE_NAME              = 'Mindgate_Upi_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::MINDGATE_UPI_REFUND;
    const BASE_STORAGE_DIRECTORY = 'UpiMindgate/Refund/Failed/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                'bankadjref'         => $row['refund']['id'],
                'flag'               => 'C',
                'shtdat'             => $this->getformattedDate($row['payment']['authorized_at'], 'Y-m-d'),
                'adjamt'             => $this->getFormattedAmount($row['refund']['amount']),
                'shser'              => $row['gateway']['npci_reference_id'],
                'shcrd'              => $row['payment']['vpa'],
                'filename'           => self::FILE_NAME,
                'reason'             => 'NA',
                'specifyother'       => $row['refund']['id'],
                'merchantaccount'    => '',
                'merchant_ifsc_code' => '',
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
