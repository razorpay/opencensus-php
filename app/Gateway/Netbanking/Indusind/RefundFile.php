<?php

namespace RZP\Gateway\Netbanking\Indusind;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Constants\MailTags;
use RZP\Constants\Mode;
use RZP\Gateway\Base;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PGRefundRAZORPAY';

    const EMAIL_BODY = 'Please forward the Indusind Netbanking refunds file to UBPS operations team';

    const BASE_STORAGE_DIRECTORY = 'Indusind/Refund/Netbanking/';

    // The columns of the file
    protected static $headers = [
        RefundFileFields::SERIAL_NO,
        RefundFileFields::TRANSACTION_ID,
        RefundFileFields::REFUND,
        RefundFileFields::REFUND_AMOUNT,
        RefundFileFields::BANK_REFERENCE_ID,
    ];

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getRefundData($input);

        $fileText = $this->getTextData($data);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Creating a file with txt format
        $creator = $this->createFile(
            FileStore\Format::TXT,
            $fileText,
            $fileName,
            FileStore\Type::INDUSIND_NETBANKING_REFUND);

        $file = $creator->get();

        $today = Carbon::now(Timezone::IST)->format('d_m_Y');

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

    protected function getRefundData($input)
    {
        $amount = 0;

        foreach ($input['data'] as $index => $row)
        {
            $data[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::REFUND             => RefundFileFields::REFUND_MODE,
                RefundFileFields::BANK               => RefundFileFields::BANK_NAME,
                RefundFileFields::REFUND_AMOUNT      => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id']
            ];

            $amount = $amount + $row['refund']['amount'] / 100;
        }

        return [$amount, $data];
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

     /*
     * @override parent class's method
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->mode === Mode::TEST)
        {
            return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . $time . $this->mode;
        }

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . $time;
    }
}
