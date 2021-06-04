<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Gateway\Mozart\NetbankingNsdl\ClaimFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Nsdl extends NetbankingBase
{
    use FileHandler;

    const FILE_NAME = 'NSDL_SETTLEMENT_';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::NSDL_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_NSDL;
    const BASE_STORAGE_DIRECTORY = 'Nsdl/Claims/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-m-Y H:i:s');

            $formattedData[] = [
                ClaimFields::PAYMENT_ID         => $row['payment']['id'],
                ClaimFields::TRANSACTION_AMOUNT => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                ClaimFields::BANK_REFERENCE_ID  => $this->fetchBankPaymentId($row),
                ClaimFields::TRANSACTION_DATE   => $date,
            ];
        }

        $prependLine = implode(',', ClaimFields::COLUMNS) . "\r\n";

        $formattedData = $this->getTextData($formattedData, $prependLine, ',');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY .static::FILE_NAME. $dateTime;
    }

    protected function fetchBankPaymentId($data)
    {
        return $data['gateway'][Netbanking::BANK_TRANSACTION_ID];
    }
}
