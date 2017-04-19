<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'IConnect_Refund_RAZORPAY';

    const EMAIL_BODY = 'Please forward the Axis Netbanking refunds file to the operations team';

    protected static $headers = [
        'Payee id', // pid
        'Payee name', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'AMOUNT',
        'DATETIME',
        'REFUND Amount',
    ];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::AXIS_NETBANKING_REFUND
        );

        $file = $creator->get();

        return [$totalAmount, $file['local_file_path']];
    }

    protected function getRefundData(array $input)
    {
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], 'Asia/Kolkata')
                    ->format('Y/m/d');

            $data[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                strtoupper($row['payment']['id']),
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date,
                number_format($row['refund']['amount'] / 100, 2, '.', '')
            ];

            $totalAmount += $row['refund']['amount'] / 100;
        }

        $initialLine = $this->getInitialLine();

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount];
    }

    protected function getInitialLine()
    {
        $data = self::$headers;

        $line = implode('~~', $data) . "\r\n";

        return $line;
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '~~', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
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

        return static::$fileToWriteName . '_' . $time . '_1';
    }
}
