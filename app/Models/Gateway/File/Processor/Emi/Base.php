<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Str;
use Mail;
use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const FILE_METADATA            = [];
    const COMPRESSION_REQUIRED     = true;
    const EMI_FILE_PASSWORD_LENGTH = 7;
    const EXTENSION                = FileStore\Format::XLSX;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $emiPaymentsForBank = $this->repo
                                   ->payment
                                   ->fetchEmiPaymentsWithCardTerminalsBetween(
                                        $begin,
                                        $end,
                                        static::BANK_CODE);

        return $emiPaymentsForBank;
    }

    public function checkIfValidDataAvailable(PublicCollection $emiPayments)
    {
        if ($emiPayments->isEmpty() === true)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    public function generateData(PublicCollection $emiPayments): array
    {
        $data['items'] = $emiPayments->all();

        $data['password'] = $this->generateEmiFilePassword();

        return $data;
    }

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteName();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(static::FILE_METADATA);

            if (static::COMPRESSION_REQUIRED === true)
            {
                $creator->password($data['password'])
                        ->compress();
            }

            $creator->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_ERROR_GENERATING_FILE,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE);
        }
    }

    public function sendFile($data)
    {
        try
        {
            $this->sendEmiPassword($data);

            $this->sendEmiFile($data);

            $this->gatewayFile->setFileSentAt(Carbon::now()->getTimestamp());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE);
        }
    }

    protected function sendEmiFile($data)
    {
        $mailData = $this->formatDataForMail($data);

        $target = $this->gatewayFile->getTarget();

        $recipients = $this->gatewayFile->getRecipients();

        $emiFileMail = new EmiMail\File(
            ucfirst($target),
            $mailData,
            $recipients
        );

        Mail::queue($emiFileMail);
    }

    protected function formatDataForMail($data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'signed_url' => $signedUrl,
            'file_name'  => $file->getLocation(),
        ];

        return $mailData;
    }

    protected function sendEmiPassword($data)
    {
        $target = $this->gatewayFile->getTarget();

        $recipients = $this->gatewayFile->getRecipients();

        $emiPasswordMail = new EmiMail\Password(
            ucfirst($target),
            $data['password'],
            $recipients
        );

        Mail::queue($emiPasswordMail);
    }

    protected function generateEmiFilePassword()
    {
        return Str::random(self::EMI_FILE_PASSWORD_LENGTH);
    }

    protected function getCardNumber(Card\Entity $card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\Tokenex)->getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function getAuthCode(Payment\Entity $payment)
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

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME;
    }

    protected function getFormattedDate($timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
                    ->format(static::DATE_FORMAT);
    }
}
