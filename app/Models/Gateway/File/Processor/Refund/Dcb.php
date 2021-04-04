<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;

class Dcb extends Base
{
    const SR_NO                 = 'Sr.No';
    const REFUND_ID             = 'Refund Id';
    const BANK_ID               = 'Bank Id';
    const MERCHANT_NAME         = 'Merchant Name';
    const PAYMENT_DATE          = 'Txn Date';
    const REFUND_DATE           = 'Refund Date';
    const BANK_MERCHANT_CODE    = 'Bank Merchant Code';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No.';
    const PGI_REFERENCE_NUMBER  = 'PGI Reference No.';
    const PAYMENT_AMOUNT        = 'Txn Amount (Rs Ps)';
    const REFUND_AMOUNT         = 'Refund Amount (Rs Ps)';

    const FILE_NAME              = 'DCB_REFUND';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::DCB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_DCB;
    const GATEWAY_CODE           = IFSC::DCBL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE              = 'DCB';
    const MERCHANT_TRANS_NAME    = 'RAZORPAY';
    const BASE_STORAGE_DIRECTORY = 'Dcb/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO                 => ++$index,
                self::REFUND_ID             => $row['refund']['id'],
                self::BANK_ID               => static::BANK_CODE,
                self::MERCHANT_NAME         => static::MERCHANT_TRANS_NAME,
                self::PAYMENT_DATE          => $this->getFormattedDate($row['payment']['created_at']),
                self::REFUND_DATE           => $this->getFormattedDate($row['refund']['created_at']),
                self::BANK_MERCHANT_CODE    => $row['terminal']['gateway_merchant_id'],
                self::BANK_REFERENCE_NUMBER => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                self::PGI_REFERENCE_NUMBER  => $row['payment']['id'],
                self::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFormattedDate($date)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format('m-d-y h:m:s');
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
