<?php

namespace RZP\Models\FundTransfer\Base;

use Mail;

use RZP\Models\FileStore;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;

abstract class Beneficiary extends BaseCore
{
    const SIGNED_URL_DURATION = '1440';

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

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail(
            $data,
            $this->channel,
            $data['merchants_count']);

        Mail::queue($beneficiaryFileMail);
    }

    protected function makeResponse(FileStore\Creator $file, int $merchantCount): array
    {
        $fileDetails   = $file->get();

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

    /**
     * Normalizes the given string based on the specification of file
     *
     * @param string|null $string
     * @param int         $length
     * @param string      $default Will be returned if the string evaluates to empty.
     *                             This will give flexibility to return different values based on the field
     * @return null|string
     */
    protected function normalizeString($string, int $length = 0, string $default = ''): string
    {
        if (empty($string) === true)
        {
            return $default;
        }

        $normalizedString =  preg_replace("/\r\n|\r|\n/", ' ', $string);

        if ($length > 0)
        {
            $normalizedString = substr($normalizedString, 0, $length);
        }

        return $normalizedString;
    }

    abstract protected function getData(PublicCollection $bankAccounts): array;

    abstract protected function generateFile($data): FileStore\Creator;
}
