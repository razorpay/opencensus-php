<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'refund_PNB_NB';

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

    protected function getTextData($data, $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0.0;

        foreach ($input['data'] as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('Ymd');

            $refund_amount = number_format($row['refund']['amount'] / 100, 2, '.', '');

            $txn_amount = number_format($row['payment']['amount'], 2, '.', '');

             // This field is left blank currently
            $cancellation_transaction_id = '';

           $data[] =[
                $row['payment']['id'],
                Constants::S_FLAG,
                $refund_amount,
                $row['gateway']['bank_payment_id'],
                $date,
                $txn_amount,
                $cancellation_transaction_id
            ];

            $totalAmount += $refund_amount;
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
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        if ($this->mode === Mode::TEST)
        {
            return join('_', [static::$fileToWriteName, $time,  'V1',  $this->mode]);
        }

        return join('_', [static::$fileToWriteName, $time,  'V1']);
    }
}
