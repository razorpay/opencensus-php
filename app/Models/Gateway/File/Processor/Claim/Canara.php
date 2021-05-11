<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Carbon\Carbon;

class Canara extends NetbankingBase
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

    const BASE_STORAGE_DIRECTORY = 'Canara/Claims/Netbanking/';

    const FILE_NAME = 'RPGNBG';

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
                $this->fetchBankAccountNumber($row),
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
        $date = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $date;
    }

    protected function fetchBankAccountNumber($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }
}
