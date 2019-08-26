<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Gateway\Mozart\NetbankingIbk\RefundFields;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Ibk extends Base
{
    use FileHandler;

    const FILE_NAME                  = '_REFUND_';
    const EXTENSION                  = FileStore\Format::XLSX;
    const FILE_TYPE                  = FileStore\Type::IBK_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_IBK;
    const PAYMENT_BANK               = 'Indian Bank';
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::IDIB;

    // This value needs be stored as this is used in the file name
    protected $gatewayMerchantId;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $this->setGatewayMerchantId($data[0]['terminal']['gateway_merchant_id']);

        $i = 0;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('m/d/y');
            $currentDate = Carbon::now(Timezone::IST)->toDateTimeString('m/d/y');

            $content[] = [
                RefundFields::SR_NO                   => $i+1,
                RefundFields::REFUND_ID               => $row['refund']['id'],
                RefundFields::BANK_ID                 => "INB",
                RefundFields::MERCHANT_NAME           => $row['terminal']['gateway_merchant_id'],
                RefundFields::TXN_DATE                => $date,
                RefundFields::REFUND_DATE             => $currentDate,
                RefundFields::BANK_MERHCANT_CODE      => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REF_NO             => $this->fetchBankPaymentId($row['gateway']['raw']),
                RefundFields::PGI_REF_NO              => $row['payment']['id'],
                RefundFields::TXN_AMOUNT              => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
            ];
            $i += 1;
        }
        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        // the serial no is hardcoded as the file is generated only once
        return "Ibk_Netbanking". self::FILE_NAME . $date;
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function setGatewayMerchantId($id)
    {
        if ($this->gatewayMerchantId === null)
        {
            $this->gatewayMerchantId = $id;
        }
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $totalAmount = array_reduce($data, function ($carry, $item)
        {
            $carry += ($item['refund']['amount'] / 100);

            return $carry;
        });

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'count'      => count($data),
            'amount'     => number_format($totalAmount, 2, '.', ''),
            'date'       => $today
        ];

        return $mailData;
    }
}
