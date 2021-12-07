<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Ubi extends Base
{
    use FileHandler;

    const ACCOUNT_NUMBER    = 'Account_Number';
    const TRAN_TYPE         = 'Tran_Type';
    const REFUND_DATE       = 'Refund_Date';
    const DATE              = 'Date';
    const PARTICULAR1       = 'Particular1';
    const PARTICULAR2       = 'Particular2';
    const AMOUNT            = 'Amount';

    const FILE_NAME              = 'refundUBI';
    const EXTENSION              = FileStore\Format::VAL;
    const FILE_TYPE              = FileStore\Type::UBI_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_UBI;
    const GATEWAY_CODE           = [IFSC::UBIN, IFSC::CORP, IFSC::ANDB, Payment\Processor\Netbanking::ANDB_C];
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Ubi/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $amount = 0;
        $index = 0;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('dmY');

            $particular1 = str_pad($row['payment']['id'], 30, ' ', STR_PAD_LEFT);
            $particular2 = str_pad($row['refund']['id'], 20, ' ', STR_PAD_LEFT);

            $transactionAmount = str_pad($row['refund']['amount'], 18, '0', STR_PAD_LEFT);

            $formattedData[] = [
                self::ACCOUNT_NUMBER      => $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER],
                self::TRAN_TYPE           => 'C',
                self::DATE                => $transactionDate,
                self::REFUND_DATE         => ' '.$refundDate,
                self::PARTICULAR1         => ' '.$particular1,
                self::PARTICULAR2         => ' '.$particular2,
                self::AMOUNT              => ' '.$transactionAmount,
            ];

            ++$index;

            $amount = $amount + $row['refund']['amount'];

        }
        $transactionAmount = str_pad($amount, 16, '0', STR_PAD_LEFT);

        $transactionDate = Carbon::now(Timezone::IST)->format('d/m/Y');

        $refundDate = Carbon::now(Timezone::IST)->format('dmY');

        $particular1 = str_pad("RAZORPAY", 30, ' ', STR_PAD_LEFT);
        $particular2 = str_pad("", 20, ' ', STR_PAD_LEFT);

        $config = $this->app['config']->get('nodal.axis');

        $formattedData[$index] = [
            self::ACCOUNT_NUMBER      => $config['account_number'],
            self::TRAN_TYPE           => 'D',
            self::DATE                => $transactionDate,
            self::REFUND_DATE         => ' '.$refundDate,
            self::PARTICULAR1         => ' '.$particular1,
            self::PARTICULAR2         => ' '.$particular2,
            self::AMOUNT              => ' '.$transactionAmount,
        ];

        $formattedData = $this->getTextData($formattedData, "", "");

        return $formattedData;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
