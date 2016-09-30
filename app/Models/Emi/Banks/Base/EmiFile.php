<?php

namespace RZP\Models\Emi\Banks\Base;

use Str;
use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class EmiFile
{
    use FileHandlerTrait;

    // Regenerated every time the EMI file is created
    protected $emiFilePassword;

    const EMI_FILE_PASSWORD_LENGTH = 7;

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();

        $this->app = \App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function generate($input)
    {
        ;
    }

    protected function getCardNumber($card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = Card\Tokenex::getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function getAuthCode($payment)
    {
        $gateway = $payment->getGateway();

        $gatewayPayment = $this->repo->$gateway->findCapturedPaymentById($payment->getId());

        return $gatewayPayment->getAuthCode();
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
        // r is monthly rate i.e a/12 * 100
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = $annualRate * 100 / 12;

        $expression = pow((1+ $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return ($num / $den);
    }

    protected function sendEmiFile($fullPath)
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $this->fetchAndSendPassword();

        $zipFile = $this->getZippedFile($fullPath);

        $data['file'] = $zipFile;

        $data['body'] = 'Please process the attached EMI file';

        $data['from'] = $this->bankName . ' Emi File';

        $data['emails'] = array_merge($this->emailIdsToSendTo, ['settlements@razorpay.com']);

        $data['subject'] = $this->bankName . ' Emi File for ' . $today;

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $message->from('emifiles@razorpay.com', $data['from']);

            $message->subject($data['subject']);

            $message->to($data['emails']);

            $message->attach($data['file']);
        });
    }

    protected function sendEmiPassword()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $data['body'] = $this->bankName . ' Emi File Password for ' . $today . " is " . $this->emiFilePassword;

        $data['from'] = $this->bankName . ' Emi File Password';

        $data['emails'] = $this->emailIdsToSendTo;

        $data['subject'] = $this->bankName . ' Emi File Password for ' . $today;

        $this->mail->queue('emails.message', $data, function ($message) use ($data, $today)
        {
            $message->from('emifiles@razorpay.com', $data['from']);

            $message->subject($data['subject']);

            $message->to($data['emails']);
        });
    }
}
