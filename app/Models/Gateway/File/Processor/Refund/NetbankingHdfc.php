<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Processor;

class NetbankingHdfc extends Processor\Base
{
    use GenerateRefundFile;

    const FILE_NAME = 'HDFC_Netbanking_Refunds';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::HDFC_NETBANKING_REFUND;

    protected $type = Payment\Entity::BANK;

    protected $gatewayCode = IFSC::HDFC;

    protected function formatDataForFile()
    {
         $i = 1;

         $formattedData = [];

        foreach ($this->data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d/m/Y');

            $formattedData[] = [
                'Sr No'            => $i++,
                'Transaction date' => $date,
                'Bank reference #' => $row['gateway']['bank_payment_id'],
                'Order #'          => $row['payment']['id'],
                'Order Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'    => $row['refund']['amount'] / 100,
                'Merchant Code'    => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $formattedData;
    }

    protected function formatDataForMail()
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
}
