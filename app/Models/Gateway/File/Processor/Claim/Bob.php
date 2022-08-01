<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Carbon\Carbon;

class Bob extends NetbankingBase
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

    const BASE_STORAGE_DIRECTORY = 'Bob/Claims/Netbanking/';

    protected function formatDataForFile($data): string
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)
                ->format('Y-m-d');

            $formattedData[] = [
                $row['payment']['id'],
                $this->fetchBankPaymentId($row),
                $date,
                number_format($row['payment']['amount'] / 100, 2, '.', '')
            ];
        }
        $initialLine = $this->getInitialLine('|');

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway']['bank_transaction_id'];
        }

        return $data['gateway']['bank_payment_id'];
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
