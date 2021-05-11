<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName;

    protected static $tpvFileName = 'Kotak_Netbanking_Claim_OTRAZORPAY';

    protected static $nonTpvFileName = 'Kotak_Netbanking_Claim_OSRAZORPAY';

    const BASE_STORAGE_DIRECTORY = 'Kotak/Claims/Netbanking/';

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

        list($txt, $totalAmount) = $this->getClaimsData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::KOTAK_NETBANKING_CLAIM);

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

        $txt = $prependLine.$txt;

        return $txt;
    }

    protected function getClaimsData($input)
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
                $row['payment']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + ($row['payment']['amount'] / 100);
        }

        $txt = $this->getTextData($data);

        return [$txt, $totalAmount];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
