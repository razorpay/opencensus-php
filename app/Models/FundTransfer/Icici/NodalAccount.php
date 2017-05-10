<?php

namespace RZP\Models\FundTransfer\Icici;

use Carbon\Carbon;
use Mail;
use phpseclib\Crypt;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class NodalAccount extends Base\Core
{
    // used in icici AES encrypter tool
    const ENCRYPTION_KEY = "1836204826394167";

    const SIGNED_URL_DURATION = '1440';

    const HEADINGS = [
        "Payment Mode",
        "Beneficiary Name",
        "Beneficiary Bank A/c No",
        "Beneficiary Bank IFSC Code",
        "Instrument Amount",
        "Payment Date",
        "Debit Account No",
        "Credit Narration",
        "Instrument Reference",
        "Dummy",
        "Dummy2",
    ];

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $mail = null;

    protected $id = null;

    public function __construct()
    {
        parent::__construct();

        $this->date = Carbon::today('Asia/Kolkata');

        $this->mail = \Mail::getFacadeRoot();

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    public function generateTransferFile($amount)
    {
        $plainText = $this->getPlainText($amount);

        $encryptedText = $this->getEncryptedText($plainText);

        $fileData = $this->createFile($encryptedText);

        $this->sendIciciTransferMail($fileData);

        return ['file' => $filePath];
    }

    protected function getPlainText($amount)
    {
        $values = [
            "N",
            "Razorpay Software Pvt Ltd",
            "7911547334",
            "KKBK0000958",
            sprintf('%0.2f', $amount),
            $this->date->format('d/m/Y'),
            "000205025290",
            "Nodal Nodal Transfer",
            $this->id,
            "",
            ""
        ];

        $this->data = array_combine(self::HEADINGS, $values);

        $csv = implode(',', $values);

        return $csv;
    }

    protected function getEncryptedText($plainText)
    {
        // create AES instance in ECB encryption mode
        $mode = Crypt\AES::MODE_ECB;

        $cipher = new Crypt\AES($mode);

        $cipher->setKey(self::ENCRYPTION_KEY);

        $encryptedText = $cipher->encrypt($plainText);

        return $encryptedText;
    }

    protected function createFile($text)
    {
        $fileName = 'icici/outgoing/NRPSS_NRPSSUPLDNEW_' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::ENC)
                        ->content($text)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->id($this->id)
                        ->metadata($metadata)
                        ->save();

        $fileInstance = $file->get();
        sd($fileInstance);

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $fileInstance['local_file_path'],
            'file_name'  => basename($fileInstance['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData['file_path'];
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }

    protected function sendIciciTransferMail(array $fileData)
    {
        $data['body'] = json_encode($this->data, JSON_PRETTY_PRINT);

        $this->mail->queue('emails.message', $data, function ($message) use ($fileData)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlements@razorpay.com', 'ICICI Transfer File');

            $today = Carbon::today('Asia/Kolkata')->format('d-m-Y');

            $message->subject("Icici Transfer files for $today");

            $message->to($emails);

            $message->attach($fileData['signed_url'], ['as' => $fileData['file_name']]);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::ICICI_SETTLEMENT_FILES);
        });
    }
}
