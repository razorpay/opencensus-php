<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'IConnect_Claim_RAZORPAY';

    const BASE_STORAGE_DIRECTORY = 'Axis/Claims/Netbanking/';

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
        list($txt, $totalAmount, $count) = $this->getClaimsData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::AXIS_NETBANKING_CLAIMS
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

    protected function getClaimsData(array $input)
    {
        $totalAmount = 0;

        $count = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('Y-m-d');

            $data[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                $row['terminal']['gateway_merchant_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date
            ];

            $totalAmount += $row['payment']['amount'] / 100;

            $count += 1;
        }

        $initialLine = $this->getInitialLine();

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount, $count];
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
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        if ($this->mode === Mode::TEST)
        {
            return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $time . '_1';
    }
}
