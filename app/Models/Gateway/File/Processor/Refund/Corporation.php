<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Corporation extends Base
{
    use FileHandler;

    const FILE_NAME                  = 'OLT_REFUNDS';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::CORPORATION_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_CORPORATION;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::CORP;
    const BASE_STORAGE_DIRECTORY     = 'Corporation/Refund/Netbanking/';

    private $mid;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        // MID remains same for all rows
        $this->setMerchantId($data[0]);

        $count  = 0;
        $amount = 0;

        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'], 'Asia/Kolkata')
                                   ->format('dmY');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], 'Asia/Kolkata')
                                  ->format('dmY');

            $formattedData[] = [
                $this->mid,
                $paymentDate,
                $row['gateway']['bank_payment_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $row['merchant']['billing_label'],
                number_format($row['refund']['amount'] / 100, 2, '.', ''),
                $refundDate,
                'Refund initiated by Customer',
                $row['refund']['id']
            ];

            ++$count;

            $amount = $amount + $row['refund']['amount'];
        }

        $totalRefundedAmount = number_format($amount / 100, 2, '.', '');

        $initialLine = 'HREC|' . $count . '|' . $totalRefundedAmount . "\r\n";

        $formattedData = $this->getTextData($formattedData, $initialLine);

        $finalLine = 'TREC**';

        $formattedData = $formattedData . "\r\n" . $finalLine;

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . $this->mid . '_' . $date . '_' . static::FILE_NAME;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $totalAmount = array_reduce(
            $data,
            function(int $carry, array $item)
            {
                $carry += $item['refund']['amount'];

                return $carry;
            },
            0);

        $totalAmount = $totalAmount / 100;

        $totalAmount = number_format($totalAmount, 2, '.', '');

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl,
            'amount'     => $totalAmount,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
    }

    private function setMerchantId($row)
    {
        if ($this->mode === Mode::LIVE)
        {
            $this->mid = $row['terminal']['gateway_merchant_id'];
        }
        else
        {
            $this->mid = $this->app['config']['gateway']['netbanking_corporation']['test_merchant_id'];
        }
    }
}
