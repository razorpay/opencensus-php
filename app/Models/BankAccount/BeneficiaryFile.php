<?php

namespace RZP\Models\BankAccount;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Models\BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;

class BeneficiaryFile extends Base\Core
{
    protected static $fileToWriteName = 'Kotak_Beneficiary_File';

    const DEFAULT_PRICING_RATE = 30000000;

    const SIGNED_URL_DURATION = '1440';

    public function generate()
    {
        $list = (new BankAccount\Repository)->getAllActivatedMerchantAccountsOrderedByCreatedAt();

        $result = $this->createBenefeciaryFile($list);

        return $result;
    }

    public function generateBetweenTimestamps($from, $to)
    {
        $list = (new BankAccount\Repository)->getMerchantBankAccountsBetweenTimestamp($from, $to);

        $result = $this->createBenefeciaryFile($list);

        return $result;
    }

    protected function createBenefeciaryFile($list)
    {
        $data = array();

        foreach ($list as $ba)
        {
            $array = [
                'Client_Code'           => 'RAZORNODAL',
                'Bene_Code'             => $ba->getBeneficiaryCode(),
                'Bene_Name'             => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_NAME),
                'Bene_Add_1'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS1),
                'Bene_Add_2'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS2),
                'Bene_Add_3'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS3),
                'Bene_Add_4'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS4),
                'Bene_Add_5'            => '',
                'Bene_City'             => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_CITY),
                'Bene_Pin'              => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_PIN),
                'State'                 => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_STATE),
                'Country'               => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_COUNTRY),
                'Bene_Email'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_EMAIL),
                'Bene_Mobile'           => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_MOBILE),
                'Bene_Tel'              => '',
                'Bene_Fax'              => '',
                'IFSC'                  => $ba->getAttribute(BankAccount\Entity::IFSC_CODE),
                'Bene_A/c No'           => $ba->getAttribute(BankAccount\Entity::ACCOUNT_NUMBER),
            ];

            array_push($data, $array);
        }

        $merchantsCount = count($list);

        $fileData = $this->generateFile($data);

        $this->sendKotakBeneficiaryFileMail($fileData, $merchantsCount);

        return ['url' => $fileData['local_file_path']];
    }

    protected function generateFile(array $data): array
    {
        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::XLSX)
                ->content($data)
                ->name($fileName)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::BENEFICIARY_FILE)
                ->save();

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $data = [
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $file['local_file_path'],
            'file_name'       => basename($file['local_file_path']),
        ];

        return $data;
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $mode = $this->mode;

        $fileName = static::$fileToWriteName . '_' . $mode . '_' . $time;

        return $fileName;
    }

    protected function sendKotakBeneficiaryFileMail(array $fileData, int $merchantsCount)
    {
        $data = $fileData + ['merchants_count' => $merchantsCount];

        $beneficiaryFileMail = new BeneficiaryFileMail($data);

        Mail::queue($beneficiaryFileMail);
    }
}
