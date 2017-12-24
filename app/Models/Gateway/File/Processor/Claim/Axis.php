<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Axis extends Base
{
    use FileHandler;

    const CORPORATE_FILE_NAME     = 'IConnect_Claim_RAZORPAY_CORP';
    const NON_CORPORATE_FILE_NAME = 'IConnect_Claim_RAZORPAY';
    const EXTENSION               = FileStore\Format::TXT;
    const FILE_TYPE               = FileStore\Type::AXIS_NETBANKING_CLAIMS;
    const GATEWAY                 = Payment\Gateway::NETBANKING_AXIS;

    const HEADERS = [
        'PayeeId', // pid
        'PayeeName', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'Amount',
        'DateTime',
    ];

    protected function fetchPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $corporate = $this->gatewayFile->getCorporate();

        $claims = $this->repo->payment->fetchCorporatePaymentsWithStatus(
            $begin,
            $end,
            static::GATEWAY,
            $statuses,
            $corporate
        );

        return $claims;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
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

        $name = ($this->gatewayFile->getCorporate() === true) ?
            static::CORPORATE_FILE_NAME :
            static::NON_CORPORATE_FILE_NAME;

        if ($this->isTestMode() === true)
        {
            return $name . '_' . $time . '_' . $this->mode . '_1';
        }

        return $name . '_' . $time . '_1';
    }
}
