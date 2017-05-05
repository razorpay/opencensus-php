<?php

namespace RZP\Gateway\Netbanking\Indusind;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;
use RZP\Constants\Mode;

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

        $fileData = [
            'file_path' => $file['local_file_path'],
            'emails'    => ['settlements@razorpay.com'],
            'subject'   => 'Indusind Netbanking refunds file for ' . $today,
            'body'      => self::EMAIL_BODY
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
                RefundFileFields::REFUND_AMOUNT      => $row['refund']['amount'] / 100,
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id']
            ];
        }

        return $this->getTextData($data);
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '||', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $this->mail->queue('emails.message', $fileData, function ($message) use ($fileData)
        {
            $emails = $fileData['emails'];

            $message->from('refunds@razorpay.com', 'Indusind Netbanking refunds');

            $message->subject($fileData['subject']);

            $message->to($emails);

            $message->attach($fileData['file_path']);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::INDUSIND_NETBANKING_REFUNDS_MAIL);
        });
    }

     /*
     * @override parent class's method
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('Ymd');

        if ($this->mode === Mode::TEST)
        {
            return static::$fileToWriteName . $time . $this->mode;
        }

        return static::$fileToWriteName . $time;
    }
}
