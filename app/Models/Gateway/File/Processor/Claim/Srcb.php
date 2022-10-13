<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Srcb extends NetbankingBase
{
    use FileHandler;

    const GATEWAY = Payment\Gateway::NETBANKING_SARASWAT;

    const HEADERS = [
        'PG Referenc Number',
        'Bank Adj Reference Number',
        'Transaction Amount',
        'Refund & narration',
        'entrydate(YYYYMMDD)'
    ];

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::SARASWAT_NETBANKING_CLAIMS;

    const FILE_NAME = 'RAZORPAY_PG_';

    const BASE_STORAGE_DIRECTORY = 'Srcb/Claims/Netbanking/';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)
                ->format('Ymd');

            $formattedData[] = [
                $row['payment']['id'],
                $this->fetchBankPaymentId($row),
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                'Y',
                $date
            ];
        }
        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine);

        return $formattedData;
    }

    protected function fetchBankPaymentId($data)
    {
        return $data['gateway']['bank_transaction_id'];
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time;
    }
}
