<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Reconciliator\NetbankingDbs\Constants;

class Dbs extends NetbankingBase
{
    const FILE_NAME = 'HCODI01.Razorpay_Claims_';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::DBS_NETBANKING_CLAIMS;
    const GATEWAY   = Payment\Gateway::NETBANKING_DBS;
    const BASE_STORAGE_DIRECTORY  = 'Dbs/Claim/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $formattedData[] = [
                Constants::MERCHANT_ID              => 'RazorPay',
                Constants::MERCHANT_ORDER_ID        => $row['payment']['id'],
                Constants::BANK_REF_NO              => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                Constants::TXN_AMOUNT               => $this->getFormattedAmount($row['payment']['amount']),
                Constants::ORDER_TYPE               => 'Order',
                Constants::STATUS                   => 'Success',
                Constants::TXN_DATE                 => $date,
                Constants::PAYMENT_ID               => '',
                Constants::PAYMENT_BANK_REF_NO      => '',
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
        $date = Carbon::now(Timezone::IST)->format('dmY_His');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $date;
    }

    protected function getStatus($payment)
    {
        $status = $payment['status'];

        if ($status == 'captured' || $status == 'authorized')
        {
            return "Success";
        }

        return "Failed";
    }
}
