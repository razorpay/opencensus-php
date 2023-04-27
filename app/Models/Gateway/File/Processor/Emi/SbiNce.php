<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Emi;
use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Emi as EmiMail;
use RZP\Constants\Environment;
use RZP\Services\Beam\Service;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Gateway\File\Type;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;
use RZP\Trace\TraceCode;

class SbiNce extends Base
{
    const BANK_CODE         = IFSC::SBIN;
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::SBI_NC_EMI_FILE;
    const FILE_TYPE_OUTPUT  = FileStore\Type::SBI_EMI_OUTPUT_FILE;
    const FILE_NAME         = 'GGNCE';
    const BEAM_FILE_TYPE    = 'emi';

    const TEST_ENCRYPTION_KEY = 'T8DIATjuwST8DIATjuwST8DIATjuwS22';

    const TEST_ENCRYPTION_IV = '123456789012';

    const S3_PATH = 'sbi_emi/';

    // redis key format: emi:sbi_emi_ref_no_<payment_id>
    const REDIS_KEY_FMT = 'emi:sbi_emi_ref_no_%s';

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $iv;

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $ncEmiPaymentsForBank = $this->repo
            ->payment
            ->fetchNoCostEmiPaymentsWithBankCode(
                $begin,
                $end,
                static::BANK_CODE);

        return $ncEmiPaymentsForBank;
    }

    public function generateEmiFilePassword()
    {
        if ($this->app->environment(Environment::TESTING) === true)
        {
            return self::TEST_ENCRYPTION_KEY;
        }

        return openssl_random_pseudo_bytes(32);
    }

    // Don't send the encryption key over email
    protected function sendEmiPassword($data)
    {
        return;
    }

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::createFile($data).
     * @param $data
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = self::S3_PATH . $this->getFileToWriteName();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $this->iv = openssl_random_pseudo_bytes(12);

            if ($this->app->environment(Environment::TESTING) === true)
            {
                $this->iv = self::TEST_ENCRYPTION_IV;
            }

            $encryptionParams = [
                Encryption\AesGcmEncryption::SECRET => $data['password'],
                Encryption\AesGcmEncryption::IV     => $this->iv,
            ];

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->store(FileStore\Store::S3)
                ->encrypt(
                    Encryption\Type::AES_GCM_ENCRYPTION,
                    $encryptionParams
                )
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->metadata($metadata);

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $fileName = $this->getFileToWriteName();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE_OUTPUT)
                ->entity($this->gatewayFile)
                ->metadata($metadata)
                ->save();
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'      => $this->gatewayFile->getId(),
                    'message' => $e->getMessage(),
                ],
                $e
            );
        }
    }

    protected function formatDataForFile($data)
    {
        $body = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        $uniqueReferenceNum = Carbon::now()->setTimezone(Timezone::IST)->format('mdyHi') . '0000';

        /**
         * @var $emiPayment Payment\Entity
         */
        foreach ($data['items'] as $emiPayment)
        {
            try
            {
                $emiPlan = $emiPayment->emiPlan;

                $merchantDetail = $emiPayment->merchant->merchantDetail;

                $terminal = $this->repo->terminal->getByMerchantIdAndGateway(
                    $emiPayment->getMerchantId(),
                    Payment\Gateway::EMI_SBI
                );

                if ($terminal === null)
                {
                    throw new LogicException(
                        'No SBI MID found for merchant',
                        null,
                        [
                            'gateway'       => 'emi_sbi',
                            'gateway_file'  => $this->gatewayFile->getId(),
                            'payment_id'    => $emiPayment['id'],
                            'merchant_id'   => $merchantDetail[Detail\Entity::MERCHANT_ID],
                        ]);
                }

                $mid = $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

                $tid = $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];

                if ($mid === null or
                    $tid === null)
                {
                    throw new LogicException(
                        'MID and TID can not be null',
                        null,
                        [
                            'gateway'     => 'emi_sbi',
                            'payment_id'  => $emiPayment['id'],
                            'merchant_id' => $merchantDetail[ Detail\Entity::MERCHANT_ID ],
                            'terminal_id' => $terminal->getId(),
                        ]);
                }

                $uniqueReferenceNum++;

                $principalAmount = $emiPayment->getAmount();

                $businessName = $this->getBusinessName($merchantDetail);

                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $subventionAmount = (int)(($principalAmount * $merchantPayback)/100);

                $redisKey = sprintf(self::REDIS_KEY_FMT, $emiPayment->getId());

                $uniqueReferenceNum = $this->cache->get($redisKey);

                $this->trace->info(TraceCode::MISC_TRACE_CODE, ['$uniqueReferenceNum_from_cache' => $uniqueReferenceNum]);

                if(isset($uniqueReferenceNum) === false)
                {
                    throw new LogicException(
                        'EMI file not generated for this payment, generate emi first before generating NCE file',
                        null,
                        [
                            'gateway'       => 'nc_emi_sbi',
                            'payment_id'    => $emiPayment['id'],
                        ]);
                }

                $card = $emiPayment->card;

                if (isset($emiPayment->card->trivia) && isset($emiPayment->token))
                {
                    $card = $emiPayment->token->card;
                }

                $body[] =
                    'DD' .    // record type always DD
                    'R' . $this->numpad($uniqueReferenceNum, 14) .
                    $this->numpad($card->getLast4(), 19) .
                    $this->numpad($principalAmount, 17) .
                    $this->strpad($this->getAuthCode($emiPayment), 6) .
                    Carbon::createFromTimestamp($emiPayment['authorized_at'], Timezone::IST)->format('dmY') .
                    $this->strpad($businessName, 40) .
                    $this->numpad($subventionAmount, 17) .
                    $this->strpad('', 76);

                $rowLength = strlen(end($body));

                $this->trace->info(TraceCode::MISC_TRACE_CODE, ['$rowLength' => $rowLength]);

                if ($rowLength !== 200)
                {
                    throw new LogicException(
                        'Row not formatted properly',
                        null,
                        [
                            'gateway'       => 'emi_sbi',
                            'length'        => $rowLength,
                            'payment_id'    => $emiPayment['id'],
                        ]);
                }

                $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                    [
                        'payment_id' => $emiPayment->getId(),
                        'bank'       => static::BANK_CODE,
                    ]
                );

                $totalTransactions++;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        $header = [
            'HH' .
            Carbon::now()->setTimezone(Timezone::IST)->format('dmY') .
            Carbon::now()->setTimezone(Timezone::IST)->format('His') .
            $this->numpad($totalTransactions, 5) .
            'F' .
            $this->strpad('', 178)
        ];

        $textRows = array_merge($header, $body);

        return implode("\r\n", $textRows);
    }

    protected function getBusinessName($merchantDetails)
    {
        $replaceArray = [
            '.',
            '!',
            '@',
            '#',
            '$',
            '%',
            '^',
            '&',
            '*',
            '(',
            ')',
            '~',
            '`',
            '_',
            '+',
            '=',
            '|',
            '\\',
            '\'',
            ':',
            ';',
            '<',
            '>',
            '?',
            '/',
            '{',
            '}',
            '-',
            '_',
            '@',
            ',',
            '[',
            ']',
            '®',
        ];

        $name = str_replace($replaceArray, " ", $merchantDetails[Detail\Entity::BUSINESS_NAME]);

        return substr($name, 0, 40);
    }

    // @codingStandardsIgnoreLine
    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    /**
     * @param $data
     * @throws GatewayErrorException
     */
    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data =  [
            Service::BEAM_PUSH_FILES         => $fileInfo,
            Service::BEAM_PUSH_JOBNAME       => BeamConstants::SBI_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            Service::BEAM_PUSH_DECRYPTION    => [
                Service::BEAM_PUSH_DECRYPTION_TYPE => Service::BEAM_PUSH_DECRYPTION_TYPE_AES256,
                Service::BEAM_PUSH_DECRYPTION_MODE => Service::BEAM_PUSH_DECRYPTION_MODE_GCM,
                Service::BEAM_PUSH_DECRYPTION_KEY  => bin2hex($data['password']),
                Service::BEAM_PUSH_DECRYPTION_IV   => bin2hex($this->iv),
            ]
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'SBI EMI - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'gateway'       => 'sbi_emi',
                ]
            );
        }

        $this->sendConfirmationMail();
    }

    protected function sendConfirmationMail()
    {
        $recipients = $this->gatewayFile->getRecipients();

        $date = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d-M-y');

        $data = [
            'body' => "Hi,\n\nThe transaction file for " . $date . " has been shared over SFTP. Please check and confirm."
        ];

        $emiFileMail = new EmiMail\File(
            'SBI NCE',
            [],
            $recipients,
            $data
        );

        Mail::queue($emiFileMail);
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }

    protected function getFileToWriteName()
    {
        // This assumes we won't be sending more than 9 files after retry.
        $start = Carbon::now()->setTimezone(Timezone::IST)->startOfDay()->getTimestamp();

        $count = $this->repo->gateway_file->fetchFileSentCountFromStart(Type::EMI, GatewayFileConstants::SBI_NCE, $start);

        return static::FILE_NAME . (string)(1) . '.' . Carbon::now()->setTimezone(Timezone::IST)->format('dmY');
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is rate/100, say a
        // $monthlyRate is a/12 i.e should be treated as .14/12
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = ($annualRate / 100) / 12;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return (round($num / $den));
    }

    protected function replaceCardNumbers($data)
    {
        $delimiter = "\r\n";

        $data = explode($delimiter, $data);

        $out = [$data[0]];

        // Replace characters from 18 till 31 which represents card numbers
        for ($i = 1; $i < sizeof($data); $i++)
        {
            $out[] = substr_replace($data[$i], '000000000000000', 17, 15);
        }

        return implode($delimiter, $out);
    }

    //-------------------------- Helpers ------------------------------------//

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    private function strpad($str, $length)
    {
        return strtoupper(str_pad($str, $length, ' ', STR_PAD_RIGHT));
    }
}
