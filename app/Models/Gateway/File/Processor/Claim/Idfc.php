<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Idfc extends Base
{
    static $filename = 'IDFC_RAZORPAY_RECON';

    const EXTENSION               = FileStore\Format::XLSX;
    const FILE_TYPE               = FileStore\Type::IDFC_NETBANKING_CLAIMS;
    const GATEWAY                 = Payment\Gateway::NETBANKING_IDFC;

    const CRN_NUMBER              = 'CRNNUMBER';
    const RAZORPAY_TRAN_ID        = 'RAZORPAYTRANID';
    const AMOUNT                  = 'AMOUNT';
    const BANK_ACC_NUMBER         = 'BANKACCNUMBER';
    const TRANSACTION_STATUS      = 'TRANSACTIONSTATUS';
    const BANK_REF_NUMBER         = 'BANKREFNUMBER';
    const BANK_ID                 = 'BANKID';
    const STATUS                  = 'Status';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                self::CRN_NUMBER            => $row['gateway']['bank_payment_id'],
                self::RAZORPAY_TRAN_ID      => $row['payment']['id'],
                self::AMOUNT                => $this->getFormattedAmountString($row['payment']['amount']),
                self::BANK_ACC_NUMBER       => '',
                self::TRANSACTION_STATUS    => 'S',
                self::BANK_REF_NUMBER       => $row['gateway']['bank_payment_id'],
                self::BANK_ID               => 'IDFC',
                self::STATUS                => 'SUCCESS',
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        return self::$filename. '_' . $time;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        return $amt;
    }
}
