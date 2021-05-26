<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'IConnect_Refund_RAZORPAY';

    const EMAIL_BODY = 'Please forward the Axis Netbanking refunds file to the operations team';

    const BASE_STORAGE_DIRECTORY     = 'Axis/Refund/Netbanking/';

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
        list($txt, $totalAmount, $count) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::AXIS_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $data = [
            'total_amount'    => $totalAmount,
            'count'           => $count,
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $file['local_file_path']
        ];

        return $data;
    }

    protected function getRefundData(array $input)
    {
        $totalAmount = 0;

        $count = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('Y/m/d');

            $data[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                $row['terminal']['gateway_merchant_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date,
                number_format($row['refund']['amount'] / 100, 2, '.', '')
            ];

            $totalAmount += $row['refund']['amount'] / 100;

            $count += 1;
        }

        $initialLine = $this->getInitialLine();

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount, $count];
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
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        if ($this->mode === Mode::TEST)
        {
            return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $time . '_1';
    }
}
