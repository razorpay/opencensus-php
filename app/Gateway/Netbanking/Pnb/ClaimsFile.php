<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\RefundStatus;

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

            $amountRefunded = $row['payment']['amount_refunded'];

            if ($amountRefunded !== 0)
            {
                $type = Constants::CREDIT;
                $amount = number_format($amountRefunded / 100, 2, '.', '');
                $txnDetails = Constants::PAYMENT;
            }
            else
            {
                $type = Constants::DEBIT;
                $amount = number_format($row['payment']['amount'] / 100, 2, '.', '');
                $txnDetails = Constants::REFUND;
            }

            $data[] = [
                $row['gateway']['account_number'],
                $row['payment']['currency'],
                Constants::SERVICE_OUTLET,
                str_pad($type, 2, ' ', STR_PAD_LEFT),
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
