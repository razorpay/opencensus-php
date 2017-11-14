<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Hdfc extends Base
{
    const FILE_NAME              = 'HDFC_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::HDFC_NETBANKING_REFUND;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = Payment\Gateway::NETBANKING_HDFC;
    const GATEWAY_CODE           = IFSC::HDFC;

    /**
     * Formats the data fetched from database as per HDFC netbanking refund file format
     */
    protected function formatDataForFile(array $data)
    {
         $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                'Sr No'            => $index + 1,
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

    /**
     * Fetches required data to be sent as part of the mail to HDFC
     */
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

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
