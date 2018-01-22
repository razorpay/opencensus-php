<?php

namespace RZP\Gateway\Netbanking\Oriental;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class RefundFile extends Base\RefundFile
{
    const DELIMITER               = '|';
    const DATE_FORMAT             = 'Ymd';

    private $date;

    public function __construct()
    {
        parent::__construct();

        $this->setTodayDate();
    }

    public function generate($input)
    {
        $creator = $this->getCreator($input);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData['file_path'];
    }

    protected function getCreator(array $input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $store = FileStore\Store::S3;

        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::TXT)
                ->content($data)
                ->name($fileName)
                ->store($store)
                ->type(FileStore\Type::ORIENTAL_NETBANKING_REFUND)
                ->save();

        return $creator;
    }

    protected function getRefundData(array $input)
    {
        $data = [$this->getInitialLine()];

        $count = 0;
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $col = [
                $row['payment']['id'],
                'R',
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
                $this->date,
                $row['payment']['amount'] / 100,
                $row['refund']['id'],
            ];

            $data[] = implode(self::DELIMITER, $col);

            $count++;

            $totalAmount += $row['refund']['amount'] / 100;
        }

        $data = array_merge($data, [$this->getLastLine($count, $totalAmount)]);

        return $this->generateText($data, '\r\n', true);
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= $row;

            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    private function getInitialLine()
    {
        $line = ['HOBCUTLPRFD', $this->date, $this->getMerchantId()];

        return implode(self::DELIMITER, $line);
    }

    private function getLastLine(int $count, int $totalAmount)
    {
        $line = ['TOBCUTLPRFD', $this->date, $count, $totalAmount];

        return implode(self::DELIMITER, $line);
    }

    private function setTodayDate()
    {
        $this->date = Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        // TODO: Merchant name or Razorpay?
        return 'REFUND_NB_OBC_MERCHANTNAME_' . $this->date;
    }

    protected function getMerchantId()
    {
        return $this->getGatewayClass()->getMerchantId();
    }

    protected function getGatewayClass()
    {
        $gateway = new Gateway();

        $gateway->setMode(Mode::TEST);

        return $gateway;
    }
}
