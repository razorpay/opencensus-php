<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Idfc extends Base
{
    use FileHandler;

    static $filename = 'Razorpay';

    const EXTENSION               = FileStore\Format::TXT;
    const FILE_TYPE               = FileStore\Type::IDFC_NETBANKING_CLAIMS;
    const GATEWAY                 = Payment\Gateway::NETBANKING_IDFC;

    const HEADERS = [
        'RAZORPAYReferenceNumber',
        'BankTransactionReferenceNo',
        'TransactionAmount',
        'STATUS',
        'TRANSACTIONDATE'
    ];

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                $row['payment']['id'],
                $row['gateway']['bank_payment_id'],
                $this->getFormattedAmountString($row['payment']['amount']),
                'SUCCESS',
                Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-M-Y H:i:s')
            ];
        }

        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return $time. '_' . self::$filename;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        return $amt;
    }
}
