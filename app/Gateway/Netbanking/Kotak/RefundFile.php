<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName;

    protected static $tpvFileName = 'Kotak_Netbanking_Refund_OTRAZORPAY';

    protected static $nonTpvFileName = 'Kotak_Netbanking_Refund_OSRAZORPAY';

    const BASE_STORAGE_DIRECTORY = 'Kotak/Refund/Netbanking/';

    protected static $headers = [
        'S.No',
        'Mer.Id',
        'Date',
        'Mer.RefNo.',
        'Amount',
        'Bank.RefNo.'];

    public function generate($input)
    {
        $this->setFileToWriteName($input);

        list($txt, $totalAmount) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::KOTAK_NETBANKING_REFUND);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $data = [
            'total_amount'    => $totalAmount,
            'url'             => $signedFileUrl,
            'local_file_path' => $file['local_file_path'],
            'name'            => basename($file['local_file_path'])
        ];

        return $data;
    }

    protected function setFileToWriteName($input)
    {
        self::$fileToWriteName = self::$nonTpvFileName;

        if (isset($input['tpv']) and
            ($input['tpv'] === true))
        {
            self::$fileToWriteName = self::$tpvFileName;
        }
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
        // S.No in this file begins with 1
        $i = 1;

        $data = [];
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d-M-Y');

            $data[] = array(
                $i++,
                $row['gateway']['merchant_code'],
                $date,
                $row['gateway']['int_payment_id'] ?: $row['gateway']['verification_id'],
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + $row['refund']['amount'] / 100;
        }

        $name = basename($this->getFileToWriteName());

        $i--;

        // First Line in the file is expected to be of the format
        // Format : FileName|ItemsCount|TotalAmount(Rs.)|CHECKSUM
        $initialLine = $name.'|'.$i.'|'.$totalAmount.'|CHECKSUM'."\r\n";

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
