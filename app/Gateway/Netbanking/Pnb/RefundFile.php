<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PNB_Netbanking_Refunds';

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getRefundData($input);

        $fileText = $this->getTextData($data);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $fileText,
            $fileName,
            FileStore\Type::PNB_NETBANKING_REFUND);

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

    protected function getRefundData($input)
    {
        $totalAmount = 0.0;

        foreach ($input['data'] as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('d/m/Y');

            $amount = number_format($row['refund']['amount'] / 100, 2, '.', '');

            $data[] = [
                $row['gateway']['account_number'],
                $row['payment']['currency'],
                Constants::SERVICE_OUTLET,
                str_pad(Constants::CREDIT, 2, ' ', STR_PAD_LEFT),
                str_pad($amount, 17, ' ', STR_PAD_LEFT),
                Constants::REFUND,
                str_pad($row['payment']['id'], 2, ' ', STR_PAD_LEFT),
                str_pad($date, 2, ' ', STR_PAD_LEFT),
            ];

            $totalAmount += $amount;
        }

        // adds row for total amount of refunds. Requested by bank.
        $data[] = [
            'RazorPay Pool A/c',
            'INR',
            '0120000',
            str_pad(Constants::DEBIT, 2, ' ', STR_PAD_LEFT),
            str_pad($totalAmount, 17, ' ', STR_PAD_LEFT),
            Constants::REFUND,
        ];

        return [$totalAmount, $data];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->mode === Mode::TEST)
        {
            return static::$fileToWriteName . $time . $this->mode;
        }

        return static::$fileToWriteName . $time;
    }
}
