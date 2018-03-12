<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Carbon\Carbon;

class Bob extends Base
{
    use FileHandler;

    const GATEWAY = 'netbanking_bob';

    const HEADERS = [
        'Razorpay Payment ID',
        'Bank Reference Number',
        'Date',
        'Amount'
    ];

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::BOB_NETBANKING_CLAIMS;

    const FILE_NAME = 'BOB_Netbanking_Claims';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)
                ->format('Y-m-d');

            $formattedData[] = [
                $row['payment']['id'],
                $row['gateway']['bank_payment_id'],
                $date,
                number_format($row['payment']['amount'] / 100, 2, '.', '')
            ];
        }
        $initialLine = $this->getInitialLine('|');

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
