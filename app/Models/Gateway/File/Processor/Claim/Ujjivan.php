<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;

class Ujjivan extends NetbankingBase
{
    const FILE_NAME = 'Success_Razorpay_';
    const EXTENSION = FileStore\Format::XLS;
    const FILE_TYPE = FileStore\Type::UJJIVAN_NETBANKING_CLAIMS;
    const GATEWAY   = Payment\Gateway::NETBANKING_UJJIVAN;
    const BASE_STORAGE_DIRECTORY  = 'Ujjivan/Claim/Netbanking/';

    protected function formatDataForFile(array $data): array
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $accountNo = $this->getAccountNo($row);

            $formattedData[]  = [
                'Transaction date'        => $paymentDate,
                'Account number'          => $accountNo,
                'Amount'                  => $this->getFormattedAmount($row['payment']['amount']),
                'Unique reference number' => $row['payment']['id'],
                'Tran ID(Finacle)'        => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                'Status'                  => "Y",
            ];
        }

        return $formattedData;
    }

    protected function getAccountNo($row)
    {
        return $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY_His');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $date;
    }
}
