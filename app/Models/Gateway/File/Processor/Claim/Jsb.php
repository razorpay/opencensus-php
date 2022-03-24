<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Mozart\NetbankingJsb\ClaimFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Jsb extends NetbankingBase
{
    use FileHandler;

    const FILE_NAME = 'razorpayPayment';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::JSB_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_JSB;
    const BASE_STORAGE_DIRECTORY      = 'Jsb/Claim/Netbanking/';

    const HEADERS = ClaimFields::CLAIM_FIELDS;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Y-M-d H:i:s');

            $date = strtoupper($date);

            $formattedData[] = [
                ClaimFields::PAYMENT_ID            => $row['payment']['id'],
                ClaimFields::BANK_REFERENCE_NUMBER => $this->fetchBankPaymentId($row),
                ClaimFields::CURRENCY              => 'INR',
                ClaimFields::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                ClaimFields::STATUS                => 'Success',
                ClaimFields::TRANSACTION_DATE      => $date,
                ClaimFields::MERCHANT_CODE         => $row['terminal']['gateway_merchant_id'],
                ClaimFields::MERCHANT_NAME         => "RAZORPAY",
            ];
        }

        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('Ydmis');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchGatewayEntities($paymentIds)
    {
        return $this->repo->mozart->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);
    }

    protected function fetchBankPaymentId($row)
    {
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $row['gateway']['data']['bank_payment_id'];
    }
}
