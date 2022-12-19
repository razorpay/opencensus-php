<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Equitas extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Equitas_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::EQUITAS_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_EQUITAS;
    const GATEWAY_CODE           = Bank::ESFB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Equitas/Refund/Netbanking/';

    const MERCHANT_ID     = 'PID';
    const REFUND_DATE     = 'Refund Date';
    const PAYMENT_ID      = 'BRN';
    const BANK_PAYMENT_ID = 'TID';
    const REFUND_AMOUNT   = 'Refamt';
    const REFUND_TYPE     = 'Refund_Type';
    const REFUND_REMARKS  = 'Refund_Remarks';
    const PURCHASE_DATE   = 'Purchase Date';
    const PURCHASE_AMOUNT = 'Purchase Amount';

    const REFUND_COLUMN_HEADERS = [
        self::MERCHANT_ID,
        self::REFUND_DATE,
        self::PAYMENT_ID,
        self::BANK_PAYMENT_ID,
        self::REFUND_AMOUNT,
        self::REFUND_TYPE,
        self::REFUND_REMARKS,
        self::PURCHASE_DATE,
        self::PURCHASE_AMOUNT,
    ];

    protected function formatDataForFile(array $data): string
    {
        $formattedData[] = self::REFUND_COLUMN_HEADERS;

        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)
                                   ->format('Y/m/d');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)
                                  ->format('Y/m/d');

            $formattedData[] = [
                self::MERCHANT_ID     => $row['terminal']['gateway_merchant_id'],
                self::REFUND_DATE     => $refundDate,
                self::PAYMENT_ID      => $row['payment']['id'],
                self::BANK_PAYMENT_ID => $this->fetchBankPaymentId($row),
                self::REFUND_AMOUNT   => $this->formatAmount($row['refund']['amount']),
                self::REFUND_TYPE     => $this->getRefundType($row),
                self::REFUND_REMARKS  => 'NA',
                self::PURCHASE_DATE   => $paymentDate,
                self::PURCHASE_AMOUNT => $this->formatAmount($row['payment']['amount']),
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $date;
    }

    protected function getRefundType($data): string
    {
        $status = 'F';

        if ($data['payment']['amount'] > $data['refund']['amount'])
        {
            $status = 'P';
        }

        return $status;
    }

    protected function formatAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankPaymentId($row)
    {
        if (($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $row['gateway']['gateway_transaction_id']; // payment through nbplus service
        }
        return $row['gateway']['reference1'];
    }
}
