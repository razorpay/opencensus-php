<?php

namespace RZP\Models\Emi\Banks\Base;

use Str;
use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\MailTags;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Emi\Banks\Base\EmiMode;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;
use RZP\Encryption\Type;

class EmiFile extends Base\Core
{
    // Regenerated every time the EMI file is created
    protected $emiFilePassword;

    protected $shouldCompress = true;

    protected $shouldEncrypt = false;

    protected $transferMode = EmiMode::MAIL;

    protected $encryptionType = Type::PGP_ENCRYPTION;

    const EMI_FILE_PASSWORD_LENGTH = 7;

    const EXTENSION = FileStore\Format::XLSX;

    // Signed Url Duration in Minutes
    const SIGNED_URL_DURATION = '15';

    public function generate($input, $email = null)
    {
        $emiData = $this->getEmiData($input);

        $this->resetEmail($email);

        $this->fetchAndSendPassword();

        $fileData = $this->generateEmiFile($emiData);

        $this->sendEmiFile($fileData);

        $this->trace->info(
            TraceCode::EMI_FILE_SENT,
            [
                'bank' => $this->bankName,
                'payment_ids' => $input->getIds()
            ]
        );

        return $fileData['signed_url'];
    }

    protected function generateEmiFile(array $emiData, array $metadata = [])
    {
        $store = FileStore\Store::S3;

        $fileName = $this->getFileToWriteName($emiData);

        $creator = new FileStore\Creator;

        $creator->extension(static::EXTENSION)
                ->content($emiData)
                ->name($fileName)
                ->store($store)
                ->type($this->type)
                ->metadata($metadata);

        if ($this->shouldEncrypt === true)
        {
            $creator->encrypt($this->encryptionType, $this->getEncryptionParams());
        }

        if ($this->shouldCompress === true)
        {
            $creator->password($this->emiFilePassword)
                    ->compress();
        }

        $creator->save();

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'signed_url' => $signedFileUrl,
            'file_name'  => basename($file['local_file_path']),
        ];

        return $fileData;
    }

    protected function getFileToWriteName(array $data)
    {
        return static::$fileToWriteName;
    }

    protected function resetEmail($email)
    {
        // if email is specified, set email id list
        // Mode should be mail only and file must be compressed
        if (empty($email) === false)
        {
            $this->emailIdsToSendTo = [$email];

            $this->transferMode = EmiMode::MAIL;

            $this->shouldCompress = true;
        }
    }

    protected function getCardNumber($card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\Tokenex)->getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function getAuthCode($payment)
    {
        $gateway = $payment->getGateway();

        $gatewayPayment = $this->repo->$gateway->findCapturedPaymentByIdOrFail($payment->getId());

        $authCode = $gatewayPayment->getAuthCode();

        if (empty($authCode) === true)
        {
            throw new Exception\LogicException(
                'Authorization Code cannot be empty.', null,
                [
                    'payment_id' => $payment->getPublicId(),
                    'auth_code'  => $authCode
                ]);
        }

        return $authCode;
    }

    protected function fetchAndSendPassword()
    {
        // skip password generation and sending for sftp
        if ($this->transferMode === EmiMode::SFTP)
        {
            return;
        }

        $this->emiFilePassword = $this->generateEmiFilePassword();

        $this->sendEmiPassword();
    }

    protected function generateEmiFilePassword()
    {
        return Str::random(self::EMI_FILE_PASSWORD_LENGTH);
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is a
        // $monthlyRate is a/12 i.e should be treated as 13/1200
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = $annualRate / 1200;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return floor($num / $den);
    }

    protected function sendEmiFile(array $fileData, $data = null)
    {
        $emiFileMail = new EmiMail\File(
            $this->bankName,
            $fileData,
            $this->emailIdsToSendTo,
            $data);

        Mail::queue($emiFileMail);
    }

    protected function sendEmiPassword()
    {
        $emiPasswordMail = new EmiMail\Password(
            $this->bankName,
            $this->emiFilePassword,
            $this->emailIdsToSendTo);

        Mail::queue($emiPasswordMail);
    }

    //Should be implemented in child class
    protected function getEncryptionParams()
    {
        return [];
    }
}
