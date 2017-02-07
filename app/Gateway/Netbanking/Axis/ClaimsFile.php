<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Mode;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'IConnect_Claims_RAZORPAY';

    const EMAIL_BODY = 'Please forward the Axis Netbanking claims file to the operations team';

    protected static $headers = [
        'PayeeId', // pid
        'PayeeName', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'Amount',
        'DateTime',
    ];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getClaimsData($input);

        $name = $this->getFileToWriteName();

        $filePath = $this->writeToTextFile($txt);

        $fileFullPath = $this->getFullFilePath($name);

        return [$totalAmount, $fileFullPath];
    }

    protected function getClaimsData($input)
    {
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], 'Asia/Kolkata')
                    ->format('Y-m-d');

            $data[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                strtoupper($row['payment']['id']),
                $row['payment']['id'],
                number_format($row['payment']['amount'] /100, 2, '.', ''),
                $date
            ];

            $totalAmount += $row['payment']['amount'] / 100;
        }

        $initialLine = $this->getInitialLine();

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount];
    }

    protected function getTextData($data, $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '~~', $ignoreLastNewline);

        return $prependLine . $txt;
    }

    protected function getInitialLine()
    {
        $data = self::$headers;

        $line = implode('~~', $data) . "\r\n";

        return $line;
    }

    /*
     * @override parent class's method
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('Ymd');

        if ($this->mode === Mode::TEST)
        {
            return static::$fileToWriteName . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::$fileToWriteName.'_'.$time.'_1';
    }
}
