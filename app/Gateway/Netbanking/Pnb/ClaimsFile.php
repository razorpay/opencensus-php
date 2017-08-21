<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PNB_Netbanking_Claims';

    public function generate($input)
    {
        list($data, $totalAmount, $count) = $this->getClaimsData($input);

        $fileText = $this->getTextData($data);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $fileText,
            $fileName,
            FileStore\Type::PNB_NETBANKING_CLAIMS);

        $file = $creator->get();

        $today = Carbon::now(Timezone::IST)->format('dmY-His');

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'local_file_path' => $file['local_file_path'],
            'signed_url'      => $signedFileUrl,
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];

        return $fileData;
    }

    protected function getTextData(array $data)
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= join($row , '');

            $txt .= "\r\n";
        }

        return $txt;
    }

    protected function getClaimsData(array $input)
    {
        $totalAmount = 0;

        $count = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('dmYHis');

            if (isset($row['payment']['refund']) === true)
            {
                $type = Constants::CREDIT;
                $amount = $row['refund']['amount'] / 100;
                $txnDetails = Constants::PAYMENT;
            }
            else
            {
                $type = Constants::DEBIT;
                $amount = $row['payment']['amount'] / 100;
                $txnDetails = Constants::REFUND;
            }

            $data[] = [
                $row['gateway']['account_number'],
                $row['payment']['currency'],
                Constants::SERVICE_OUTLET,
                $type,
                str_pad($amount, 17, ' ', STR_PAD_LEFT),
                $txnDetails,
                $date,
            ];

            $totalAmount += $amount;

            $count++;
        }

        return [$data, $totalAmount, $count];
    }
}
