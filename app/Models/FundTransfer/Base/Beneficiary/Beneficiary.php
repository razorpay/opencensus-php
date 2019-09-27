<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use Mail;

use RZP\Models\FundAccount\Type;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;

abstract class Beneficiary extends BaseCore
{
    protected $accountType = Type::BANK_ACCOUNT;

    /**
     * @param $accounts
     * @param $accountType
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     */
    public function register(PublicCollection $accounts, $accountType = Type::BANK_ACCOUNT, array $input = []): array
    {
        $this->accountType = $accountType;

        $response = $this->registerBeneficiary($accounts);

        if ((array_key_exists('send_email', $input) === true) and
            ((bool)$input['send_email'] === false))
        {
            return $response;
        }

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        return $response;
    }

    /**
     * @param $accounts
     * @param $accountType
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     */
    public function verify(PublicCollection $accounts, $accountType = Type::BANK_ACCOUNT): array
    {
        $this->accountType = $accountType;

        $response = $this->verifyBeneficiary($accounts);

        return $response;
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail(
            $data,
            $this->channel,
            $data['register_count']);

        Mail::queue($beneficiaryFileMail);
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

    abstract public function registerBeneficiary(PublicCollection $bankAccounts): array;

    abstract public function verifyBeneficiary(PublicCollection $bankAccounts): array;
}
