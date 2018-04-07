<?php

namespace RZP\Models\FundTransfer\Kotak;

use Mail;
use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Base;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;

class Beneficiary extends Base\Beneficiary
{
    protected $channel = Channel::KOTAK;

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
        $data = $this->getData($bankAccounts);

        $file = $this->generateFile($data);

        $merchantCount = count($data);

        $response = $this->makeResponse($file, $merchantCount);

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        return $response;
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $data = [];

        foreach ($bankAccounts as $ba)
        {
            $beneName = $ba->getAttribute(BankAccount::BENEFICIARY_NAME) ?: '';

            $beneName = substr($beneName, 0, 40);

            $merchantDetails = $ba->source->merchantDetail;

            $array = [
                'Client_Code'           => 'RAZORNODAL',
                'Bene_Code'             => $ba->getBeneficiaryCode(),
                'Bene_Name'             => $beneName,
                'Bene_Add_1'            => substr($merchantDetails->getBusinessRegisteredState(), 0, 30),
                'Bene_Add_2'            => '',
                'Bene_Add_3'            => '',
                'Bene_Add_4'            => '',
                'Bene_Add_5'            => '',
                'Bene_City'             => substr($merchantDetails->getBusinessRegisteredCity(), 0, 30),
                'Bene_Pin'              => substr($merchantDetails->getBusinessRegisteredPin(), 0, 6),
                'State'                 => substr($merchantDetails->getBusinessRegisteredState(), 0, 2),
                'Country'               => 'IN',
                'Bene_Email'            => $merchantDetails->getContactEmail(),
                'Bene_Mobile'           => substr($merchantDetails->getContactMobile(), 0, 32),
                'Bene_Tel'              => '',
                'Bene_Fax'              => '',
                'IFSC'                  => $ba->getAttribute(BankAccount::IFSC_CODE),
                'Bene_A/c No'           => $ba->getAttribute(BankAccount::ACCOUNT_NUMBER),
            ];

            array_push($data, $array);
        }

        return $data;
    }

    protected function generateFile(array $data): FileStore\Creator
    {
        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($data)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::BENEFICIARY_FILE)
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

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $mode = $this->mode;

        $fileName = 'Kotak_Beneficiary_File' . '_' . $mode . '_' . $time;

        return $fileName;
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['merchants_count']);

        Mail::queue($beneficiaryFileMail);
    }
}