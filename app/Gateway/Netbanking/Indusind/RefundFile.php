<?php

namespace RZP\Gateway\Netbanking\Indusind;

use Carbon\Carbon;
use Mail;

use RZP\Constants\MailTags;
use RZP\Constants\Mode;
use RZP\Gateway\Base;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PGReconRAZORPAY';

    const EMAIL_BODY = 'Please forward the Indusind Netbanking refunds file to UBPS operations team';

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
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Creating a file with txt format
        $creator = $this->createFile(
            FileStore\Format::TXT,
            $data,
            $fileName,
            FileStore\Type::INDUSIND_NETBANKING_REFUND);

        $file = $creator->get();

        $today = Carbon::now('Asia/Kolkata')->format('d_m_Y');

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'signed_url' => $signedFileUrl,
            'file_name'  => basename($file['local_file_path']),
        ];

        $this->sendRefundEmail($fileData);

        return $fileData['file_path'];
    }

    protected function getRefundData($input)
    {
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
        }

        return $this->getTextData($data);
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_INDUSIND);

        Mail::queue($refundFileMail);
    }

     /*
     * @override parent class's method
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('dmY');

        if ($this->mode === Mode::TEST)
        {
            return static::$fileToWriteName . $time . $this->mode;
        }

        return static::$fileToWriteName . $time;
    }
}
