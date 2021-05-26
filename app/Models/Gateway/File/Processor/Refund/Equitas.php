<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Equitas extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Equitas_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::EQUITAS_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_EQUITAS;
    const GATEWAY_CODE           = IFSC::ESFB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Equitas/Refund/Netbanking/';

    const MERCHANT_ID           = 'PID';
    const REFUND_DATE           = 'Refund Date';
    const PAYMENT_ID            = 'BRN';
    const BANK_PAYMENT_ID       = 'TID';
    const REFUND_AMOUNT         = 'Refamt';
    const REFUND_TYPE           = 'Refund_Type';
    const REFUND_REMARKS        = 'Refund_Remarks';
    const PURCHASE_DATE         = 'Purchase Date';
    const PURCHASE_AMOUNT       = 'Purchase Amount';

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

    protected $config;

    protected function formatDataForFile(array $data)
    {
        $formattedData[] = self::REFUND_COLUMN_HEADERS;

        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp(
                           $row['payment']['created_at'],
                           Timezone::IST)
                           ->format('Y/m/d');

            $refundDate = Carbon::createFromTimestamp(
                          $row['refund']['created_at'],
                          Timezone::IST)
                          ->format('Y/m/d');

            $formattedData[] = [
                self::MERCHANT_ID          => $this->getMerchantId($row),
                self::REFUND_DATE          => $refundDate,
                self::PAYMENT_ID           => $row['payment']['id'],
                self::BANK_PAYMENT_ID      => $row['gateway']['reference1'],
                self::REFUND_AMOUNT        => $this->formatAmount($row['refund']['amount']),
                self::REFUND_TYPE          => $this->getRefundType($row),
                self::REFUND_REMARKS       => 'NA',
                self::PURCHASE_DATE        => $paymentDate,
                self::PURCHASE_AMOUNT      => $this->formatAmount($row['payment']['amount']),
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $date;
    }

    protected function getMerchantId($input)
    {
        $mid = $input['terminal']['gateway_merchant_id'];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config['test_merchant_id'];
        }

        return $mid;
    }

    protected function getRefundType($data)
    {
        $status = 'F';

        if ($data['payment']['amount'] > $data['refund']['amount'])
        {
            $status = 'P';
        }

        return $status;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
