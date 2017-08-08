<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Models\Gateway\File\Processor;

class NetbankingIcici extends Processor\Base
{
    use GenerateRefundFile;

    const FILE_NAME     = 'Icici_Netbanking_Refunds';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::ICICI_NETBANKING_REFUND;

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], 'Asia/Kolkata')->format('jS F Y');

            $formattedData[] = [
                'Sr No'                 => $index + 1,
                'Payee_id'              => $row['terminal']['gateway_merchant_id'],
                'SPID'                  => $row['terminal']['gateway_merchant_id2'],
                'Bank Reference No.'    => $row['gateway']['bank_payment_id'],
                'Transaction Date'      => $date,
                'Transaction Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount'         => $row['refund']['amount'] / 100,
                'Transaction Id'        => $row['payment']['id'],
                'Reversal/Cancellation' => 'C',
                'Remarks'               => '',
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

        $totalAmount = array_reduce($this->data, function ($carry, $item)
        {
            $carry += ($item['refund']['amount'] / 100);

            return $carry;
        });

        $today = Carbon::now('Asia/Kolkata')->format('jS F Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'count'      => count($this->data),
            'amount'     => number_format($totalAmount, 2, '.', ''),
            'date'       => $today
        ];

        return $mailData;
    }
}
