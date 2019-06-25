<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Carbon\Carbon;

class Canara extends Base
{
    use FileHandler;

    const GATEWAY = 'netbanking_canara';

    const HEADERS = [
        'Razorpay Payment ID',
        'Bank Reference Number',
        'Transaction Date',
        'Amount',
        'Status'
    ];

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::CANARA_NETBANKING_CLAIMS;

    const FILE_NAME = 'Canara_Netbanking_Claims';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)
                ->format('d-m-Y');

            $formattedData[] = [
                $row['payment']['id'],
                $row['gateway']['bank_payment_id'],
                $date,
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                'SUCCESS'
            ];
        }

        $initialLine = $this->getInitialLine('|');

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        return static::FILE_NAME;
    }
}
