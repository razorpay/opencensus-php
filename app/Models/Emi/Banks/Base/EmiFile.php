<?php

namespace RZP\Models\Emi\Banks\Base;

use App;
use Str;
use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\MailTags;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Services\Beam\Service;
use RZP\Models\Emi\Banks\Base\EmiMode;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;
use RZP\Encryption\Type;
use RZP\Mail\Base\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;

class EmiFile extends Base\Core
{
    // Regenerated every time the EMI file is created
    protected $emiFilePassword;

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $shouldCompress = true;

    protected $shouldEncrypt = false;

    protected $transferMode = EmiMode::MAIL;

    protected $encryptionType = Type::PGP_ENCRYPTION;

    const EMI_FILE_PASSWORD_LENGTH = 7;

    const EXTENSION = FileStore\Format::XLSX;

    // Signed Url Duration in Minutes
    const SIGNED_URL_DURATION = '15';

    const CPS_AUTHORIZATION_RRN                    = 'rrn';
    const CPS_AUTHORIZATION_GATEWAY_MERCHANT_ID    = 'gateway_merchant_id';
    const CPS_AUTHORIZATION_GATEWAY_TRANSACTION_ID = 'gateway_transaction_id';

    const CPS_PARAMS = [
        self::CPS_AUTHORIZATION_RRN,
        self::CPS_AUTHORIZATION_GATEWAY_MERCHANT_ID,
        self::CPS_AUTHORIZATION_GATEWAY_TRANSACTION_ID,
    ];

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

        $this->trace->info(TraceCode::EMI_FILE_NAME,
            [
                'file_name' => $fileName,
                'extension' => static::EXTENSION
            ]
        );
        $creator = new FileStore\Creator;

        $creator->extension(static::EXTENSION)
                ->content($emiData)
                ->name($fileName)
                ->store($store)
                ->type($this->type)
                ->metadata($metadata);

        $this->file = $creator->getFileInstance();

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

            $this->shouldEncrypt = false;
        }
    }

    protected function getCardNumber($card,$gateway=null)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);

        return $cardNumber;
    }

    protected function getAuthCode($payment)
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

    protected function fetchAndSendPassword()
    {
        $this->emiFilePassword = $this->generateEmiFilePassword();

        // skip password sending for sftp
        if ($this->transferMode === EmiMode::SFTP)
        {
            return;
        }

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

    protected function pushEmiFileToBeam(string $jobName)
    {
        try
        {
            $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

            $fileInfo = [$fullFileName];

            $bucketConfig = $this->getBucketConfig();

            $data =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => $jobName,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'settlements',
                'filetype'  => 'emi',
                'subject'   => 'File Send failure',
                'recipient' => [
                    Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                    Constants::MAIL_ADDRESSES[Constants::FINOPS],
                    Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
                ],
            ];

            $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::BEAM_PUSH_FAILED,
            [
                'job_name'  => $jobName,
                'file_name' => $fullFileName,
            ]);
        }
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

    protected function fetchDataFromCardPaymentService($input)
    {

        $paymentIds = array_pluck($input, 'id');

        $request = [
            'fields'        => self::CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        try
        {
            $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

            $this->trace->info(
                TraceCode::CARD_PAYMENT_SERVICE_RESPONSE,
                [
                    'payment_ids' => $paymentIds,
                    'response'    => $response,
                ]);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::CARD_PAYMENT_SERVICE_ERROR,
                [
                    'request'   => $request,
                ]);

            throw $ex;
        }

        return $response;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);

        return $config[$bucketType];
    }
}
