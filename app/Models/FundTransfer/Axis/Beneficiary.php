<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Config;
use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Encryption\AESEncryption;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Models\FundTransfer\Base\Beneficiary as BaseBeneficiary;

class Beneficiary extends BaseBeneficiary
{
    protected $id;

    protected $channel = Channel::AXIS;

    protected $secret;

    protected $iv;

    public function __construct()
    {
        parent::__construct();

        $this->id = Base\UniqueIdEntity::generateUniqueId();

        $this->secret = Config::get('nodal.axis.secret');

        $this->iv = base64_decode(Config::get('nodal.axis.iv'));
    }

    /**
     * @param $bankAccounts
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     *                         'merchants_count'
     */
    public function register(PublicCollection $bankAccounts, array $input = []): array
    {
        $rows = $this->getData($bankAccounts);

        $file = $this->generateFile($rows);

        $merchantCount = count($rows);

        $response = $this->makeResponse($file, $merchantCount);

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        return $response;
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        $headers = [
            'Beneficiary Code',
            'Beneficiary Name',
            'Beneficiary Account',
            'Bene Bank IFSC',
            'Beneficiary Bank Name',
        ];

        $rows[] = $headers;

        foreach ($bankAccounts as $ba)
        {
            $beneName = $ba->getBeneficiaryName();

            $beneName = substr($beneName, 0, 50);

            $ifsc = strtoupper($ba->getIfscCode());

            $rows[] = [
                $ba->getId(),
                $beneName,
                $ba->getAccountNumber(),
                $ifsc,
                '', // Axis needs us to send this value as blank
            ];
        }

        return $rows;
    }

    protected function generateFile(array $rows): FileStore\Creator
    {
        $fileName = 'axis/beneficiary/' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($rows)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->encrypt(Type::AES_ENCRYPTION, [
                            AESEncryption::MODE   => AES::MODE_CBC,
                            AESEncryption::IV     => $this->iv,
                            AESEncryption::SECRET => $this->secret,])
                        ->encode()
                        ->save();

        return $file;
    }

    protected function makeResponse(FileStore\Creator $file, int $merchantCount)
    {
        $fileDetails = $file->get();

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $data = [
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $fileDetails['local_file_path'],
            'file_name'       => basename($fileDetails['local_file_path']),
            'merchants_count' => $merchantCount,
            'channel'         => $this->channel,
        ];

        return $data;
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['merchants_count']);

        Mail::queue($beneficiaryFileMail);
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10003',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }
}