<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Trace\TraceCode;
use Str;
use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Gateway\File\Status;
use RZP\Services\CardPaymentService;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const FILE_METADATA            = [];
    const COMPRESSION_REQUIRED     = true;
    const EMI_FILE_PASSWORD_LENGTH = 7;
    const EXTENSION                = FileStore\Format::XLSX;

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $totalTransactions;

    protected $encryptionType = Type::PGP_ENCRYPTION;

    protected $shouldEncrypt = false;

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

    protected function getEncryptionParams()
    {
        return [];
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

            $this->trace->info(TraceCode::EMI_FILE_NAME,
                [
                    'payment_id' => static::BANK_CODE,
                    'file_name'  => $fileName,
                ]
            );

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(static::FILE_METADATA);

            if ($this->shouldEncrypt === true)
            {
                $creator->encrypt($this->encryptionType, $this->getEncryptionParams());
            }

            if (static::COMPRESSION_REQUIRED === true)
            {
                $creator->password($data['password'])
                        ->compress();
            }

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE, [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    /**
     * @param $data
     * @throws GatewayFileException
     */
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
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                ['id' => $this->gatewayFile->getId()],
                $e
            );
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

    protected function getCardNumber(Card\Entity $card,$gateway=null)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);

        return $cardNumber;
    }

    protected function fetchRrnDetails($data)
    {
        $paymentIds = array_pluck($data['items'], 'id');

        $request = [
            'fields'      => [CardPaymentService::RRN],
            'payment_ids' => $paymentIds,
        ];

        return $this->app['card.payments']->fetchAuthorizationData($request);
    }

    protected function getAuthCode(Payment\Entity $payment)
    {
        $authCode = $payment->getReference2();

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

        return (floor($num / $den));
    }

    protected function fetchCPSAuthorizationData($data, $fieldsToBeFetched)
    {
        $paymentIds = array();

        foreach ($data['items'] as $entity)
        {
            if($entity->getEntity() === 'payment')
            {
                $paymentIds[] = $entity->getId();
            }
            else
            {
                $paymentIds[] = $entity->getPaymentId();
            }
        }

        $request = [
            'fields'      => $fieldsToBeFetched,
            'payment_ids' => $paymentIds,
        ];

        return $this->app['card.payments']->fetchAuthorizationData($request);
    }
}
