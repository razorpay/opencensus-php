<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Gateway\Base;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Kotak_Netbanking_Claim';

    protected static $headers = [
        'S.No',
        'Mer.Id',
        'Date',
        'Mer.RefNo.',
        'Amount',
        'Bank.RefNo.'];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getClaimsData($input);

        $name = $this->getFileToWriteName();

        $filePath = $this->writeToTextFile($txt);

        $fileFullPath = $this->getFullFilePath($name);

        return [$totalAmount, $fileFullPath];
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
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d-M-Y');

            $data[] = array(
                $i++,
                $row['gateway']['merchant_code'],
                $date,
                $row['gateway']['int_payment_id'],
                $row['payment']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + ($row['payment']['amount'] / 100);
        }

        $txt = $this->getTextData($data);

        return [$txt, $totalAmount];
    }
}
