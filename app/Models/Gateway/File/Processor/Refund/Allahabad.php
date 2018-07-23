<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Allahabad extends Base
{
    use FileHandler;

    const FILE_NAME                   = 'Allahabad_REFUND';
    const EXTENSION                   = FileStore\Format::TXT;
    const FILE_TYPE                   = FileStore\Type::ALLAHABAD_NETBANKING_REFUND;
    const GATEWAY                     = Payment\Gateway::NETBANKING_ALLAHABAD;
    const GATEWAY_CODE                = IFSC::ALLA;
    const PAYMENT_TYPE_ATTRIBUTE      = Payment\Entity::BANK;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $txnDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $pid = 'Razor';

            $formattedData[] = [
                'PID'                   => $pid,
                'Bank Id'               => '027',
                'Merchant Name'         => '',
                'Txn Date'              => $txnDate,
                'Refund Date'           => $refundDate,
                'Bank Merchant Code'    => '',
                'Bank Ref No.'          => '',
                'PGI Reference No.'     => '',
                'Txn Amount'            => $this->formatAmount($row['payment']['amount'] / 100),
                'Refund'                => $this->formatAmount($row['refund']['amount'] / 100),
            ];


        }
        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getTextData($data)
    {
        $txt  = $this->generateText($data,'|',true);

        return $txt;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => $file->getLocation(),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }


    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return self::FILE_NAME.'_'.$time;
    }

    public function formatAmount($amount): string
    {
        return number_format($amount , 2, '.', '');
    }
}