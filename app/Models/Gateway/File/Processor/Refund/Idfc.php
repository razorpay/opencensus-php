<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Idfc extends Base
{
    use FileHandler;

    static $filename = 'Razorpay_REFUND';

    const FILE_TYPE              = FileStore\Type::IDFC_NETBANKING_REFUND;
    const BASE_STORAGE_DIRECTORY = 'Idfc/Refund/Netbanking/';
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY_CODE           = IFSC::IDFB;
    const GATEWAY                = Payment\Gateway::NETBANKING_IDFC;
    const EXTENSION              = FileStore\Format::XLSX;

    const SR_NO                  = 'Sr. No';
    const REFUND_ID              = 'Refund Id';
    const BANK_ID                = 'Bank Id';
    const MERCHANT_NAME          = 'Merchant Name';
    const TXN_DATE               = 'Txn Date';
    const REFUND_DATE            = 'Refund Date';
    const BANK_MERCHANT_CODE     = 'Bank Merchant Code';
    const BANK_REF_NO            = 'Bank Ref No.';
    const PGI_REF_NO             = 'PGI Reference No.';
    const TXN_AMT                = 'Txn Amount (Rs Ps)';
    const REFUND_AMT             = 'Refund Amount (Rs Ps)';

    protected static $headers = [
        self::SR_NO,
        self::REFUND_ID,
        self::BANK_ID,
        self::MERCHANT_NAME,
        self::TXN_DATE,
        self::REFUND_DATE,
        self::BANK_MERCHANT_CODE,
        self::BANK_REF_NO,
        self::PGI_REF_NO,
        self::TXN_AMT,
        self::REFUND_AMT,
    ];

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return static::BASE_STORAGE_DIRECTORY . static::$filename . '_' . $time;
    }

    protected function formatDataForFile(array $data)
    {
        $i = 1;

        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO                 => $i++,
                self::REFUND_ID             => $row['refund']['id'],
                self::BANK_ID               => 'IDN',
                self::MERCHANT_NAME         => $row['terminal']['gateway_merchant_id'],
                self::TXN_DATE              => $this->getDateFromTimestramp($row['payment']['created_at']),
                self::REFUND_DATE           => $this->getDateFromTimestramp($row['refund']['created_at']),
                self::BANK_MERCHANT_CODE    => $row['terminal']['gateway_merchant_id'],
                self::BANK_REF_NO           => $this->fetchBankReferenceId($row),
                self::PGI_REF_NO            => $row['payment']['id'],
                self::TXN_AMT               => $this->getFormattedAmountString($row['payment']['amount']),
                self::REFUND_AMT            => $this->getFormattedAmountString($row['refund']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => basename($file->getLocation()),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function getDateFromTimestramp($timestramp)
    {
        $time = Carbon::createFromTimestamp($timestramp,Timezone::IST)
            ->format('m/d/Y');

        return $time;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        return $amt;
    }

    protected function fetchBankReferenceId($row)
    {
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $row['gateway']['bank_payment_id'];
    }
}
