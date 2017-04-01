<?php

namespace RZP\Models\Emi\Banks\Base;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Card;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Constants\MailTags;
use Str;

class EmiFile extends Base\Core
{
    use FileHandlerTrait;

    // Regenerated every time the EMI file is created
    protected $emiFilePassword;

    const EMI_FILE_PASSWORD_LENGTH = 7;

    public function __construct()
    {
        parent::__construct();

        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        $emiData = $this->getEmiData($input);

        $emiFile = $this->writeEmiFile($emiData);

        $this->sendEmiFile($emiFile['path']);

        $this->trace->info(
            TraceCode::EMI_FILE_SENT,
            ['bank' => $this->bankName, 'payment_ids' => $input->getIds()]
        );

        return $emiFile['url'];
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
                'Authorization Code cannot be empty.', null, ['auth_code' => $authCode]);
        }

        return $authCode;
    }

    protected function fetchAndSendPassword()
    {
        $this->emiFilePassword = $this->generateEmiFilePassword();

        $this->sendEmiPassword();
    }

    protected function generateEmiFilePassword()
    {
        return Str::random(self::EMI_FILE_PASSWORD_LENGTH);
    }

    protected function getZippedFile($fullPath)
    {
        $fileArray = array($fullPath);

        $zipPath = $this->makeZipFile($fileArray, $this->emiFilePassword);

        return $zipPath;
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

    protected function sendEmiFile($fullPath)
    {
        $this->fetchAndSendPassword();

        $zipFile = $this->getZippedFile($fullPath);

        $emiFileMail = new EmiMail\File($this->bankName, $zipFile);

        $this->mail->send($emiFileMail);
    }

    protected function sendEmiPassword()
    {
        $emiPasswordMail = new EmiMail\Password($this->bankName, $this->emiFilePassword);

        $this->mail->send($emiPasswordMail);
    }
}
