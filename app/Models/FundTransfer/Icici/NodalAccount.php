<?php

namespace RZP\Models\FundTransfer\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use phpseclib\Crypt;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Base as NodalBase;
use RZP\Mail\Settlement as SettlementMail;

class NodalAccount extends NodalBase\NodalAccount
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

    const MODE_MAPPING = [
        Mode::NEFT    => 'N',
        Mode::RTGS    => 'R',
        Mode::IMPS    => 'I',
    ];

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $id = null;

    public function __construct()
    {
        parent::__construct();

        $this->date = Carbon::today(Timezone::IST);

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    public function generateTransferFile($amount)
    {
        $plainText = $this->getPlainText($amount);

        $encryptedText = $this->getEncryptedText($plainText);

        $fileData = $this->createFile($encryptedText);

        $this->sendIciciTransferMail($fileData);

        return ['file' => $fileData['file_path']];
    }

    protected function getPlainText($amount)
    {
        $mode = $this->getTransferMode($amount);

        $this->mode = self::MODE_MAPPING[$mode];

        $values = [
            $this->mode,
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

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $fileInstance['local_file_path'],
            'file_name'  => basename($fileInstance['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendIciciTransferMail(array $fileData)
    {
        $data['body'] = json_encode($this->data, JSON_PRETTY_PRINT);

        $data['file_data'] = $fileData;

        $iciciSettlementMail = new SettlementMail\IciciSettlement($data);

        Mail::queue($iciciSettlementMail);
    }
}
