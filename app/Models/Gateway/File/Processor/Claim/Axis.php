<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Axis extends NetbankingBase
{
    use FileHandler;

    const CORPORATE_FILE_NAME     = 'IConnect_Claim_RAZORPAY_CORP';
    const NON_CORPORATE_FILE_NAME = 'IConnect_Claim_RAZORPAY';
    const EXTENSION               = FileStore\Format::TXT;
    const FILE_TYPE               = FileStore\Type::AXIS_NETBANKING_CLAIMS;
    const GATEWAY                 = Payment\Gateway::NETBANKING_AXIS;
    const BASE_STORAGE_DIRECTORY  = 'Axis/Claims/Netbanking/';

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

        $bankCode = (($corporate === true) ? Payment\Processor\Netbanking::UTIB_C : IFSC::UTIB);

        $claims = $this->repo->payment->fetchCorporatePaymentsWithStatusAndRelations(
            $begin,
            $end,
            static::GATEWAY,
            $bankCode,
            ['terminal']);

        $claims = $claims->reject(function($claim)
        {
            return (($claim->isEmandate() === true) or ($claim->terminal->isDirectSettlement() === true));
        });

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
                $this->fetchBankPaymentId($row),
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
            return static::BASE_STORAGE_DIRECTORY . $name . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::BASE_STORAGE_DIRECTORY . $name . '_' . $time . '_1';
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS)))
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id']; // payment through api
    }
}
