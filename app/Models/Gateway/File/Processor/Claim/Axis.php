<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Axis extends Processor\Base
{
    use GenerateClaimFile;
    use FileHandler;

    const FILE_NAME     = 'IConnect_Claim_RAZORPAY';
    const EXTENSION     = FileStore\Format::TXT;
    const FILE_TYPE     = FileStore\Type::AXIS_NETBANKING_CLAIMS;
    const GATEWAY       = Payment\Gateway::NETBANKING_AXIS;
    const DATE_FORMAT   = 'Ymd';

    const HEADERS = [
        'PayeeId', // pid
        'PayeeName', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'Amount',
        'DateTime',
    ];

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], 'Asia/Kolkata')
                    ->format('Y-m-d');

            $formattedData[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                $row['terminal']['gateway_merchant_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date
            ];
        }

        $initialLine = $this->getInitialLine('~~');

        $formattedData = $this->getTextData($formattedData, $initialLine, '~~');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        if ($this->isTestMode() === true)
        {
            return static::FILE_NAME . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::FILE_NAME . '_' . $time . '_1';
    }
}
