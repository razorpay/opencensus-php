<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Reconciliator\NetbankingAusf\Constants;

class Aubl extends NetbankingBase
{
    const FILE_NAME = 'Success Transaction Format.xlsx';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::AUBL_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_AUSF;
    const BASE_STORAGE_DIRECTORY  = 'Aubl/Claim/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $dateVerbose = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i');

            $formattedData[] = [
                Constants::Date                     => $date,
                Constants::TRANSACTION_TYPE         => 'PAYMENT',
                Constants::PAYMENT_ID               => $row['payment']['id'],
                Constants::PAYMENT_ID_EXT           => '',
                Constants::MERCHANT_CODE            => 'RAZORPAY',
                Constants::USERREFERENCENO          => $row['payment']['id'],
                Constants::EXTERNALREFERENCEID      => $this->fetchBankPaymentId($row),
                Constants::EXTERNALREFERENCEID_EXT  => '',
                Constants::TRANSACTION_DATE         => $dateVerbose,
                Constants::AMOUNT                   => $this->getFormattedAmount($row['payment']['amount']),
                Constants::REFUND_AMOUNT            => '',
                Constants::SERVICE_CHARGES_AMOUNT   => '0',
                Constants::DEBIT_ACCOUNT_ID         => $row['gateway']['bank_account_number'],
                Constants::STATUS                   => 'COM',
                Constants::MERCHANT_ACCOUNT_NUMBER  => '2121201131751367',  //TODO: TO BE DISCUSSED
                Constants::MERCHANT_URL             => 'www.razorpay.com',
            ];
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::BASE_STORAGE_DIRECTORY . strtr(self::FILE_NAME, ['{$date}' => $date]);
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID];
        }

        return $data['gateway']['data']['bank_payment_id'];
    }
}
