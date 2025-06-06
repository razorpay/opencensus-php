<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Mail;
use Carbon\Carbon;

use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
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

class Sbi extends Base
{
    const BANK_CODE         = IFSC::SBIN;
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::SBI_EMI_FILE;
    const FILE_TYPE_OUTPUT  = FileStore\Type::SBI_EMI_OUTPUT_FILE;
    const FILE_NAME         = 'GGCMS';
    const BEAM_FILE_TYPE    = 'emi';

    const TEST_ENCRYPTION_KEY = 'T8DIATjuwST8DIATjuwST8DIATjuwS22';

    const TEST_ENCRYPTION_IV = '123456789012';

    const S3_PATH = 'sbi_emi/';

    // 10 days = 240 * 60 * 60 = 864000
    const REDIS_KEY_TTL = 864000;

    // Starting from this date (29/08/2023), SKU ID will be generated using SKU_PREFIX_V2(GG0003).
    // Hence will be sending the same if creation date of the terminal is after this
    const SKU_V2_START_TIMESTAMP =  1693247400; // 29/08/2023 00:00:00

    const EMI_RATES_CHANGE_START_TIMESTAMP = 1705516200; // 18/01/2024 00:00:00

    const OLD_EMI_RATES = [
        18 => '1600',
        24 => '1600',
    ];

    const SKU_PREFIX_V1 = 'GG0001';
    const SKU_PREFIX_V2 = 'GG0003';
    // redis key format: emi:sbi_emi_ref_no_<payment_id>
    const REDIS_KEY_FMT = 'emi:sbi_emi_ref_no_%s';

    const AMOUNT = 'amount';

    const MIN_AMOUNT = 'min_amount';
    //processing fee
    const PROCESSING_FEES = [
        3  => [
            self::AMOUNT => '0000',
        ],
        6  => [
            self::AMOUNT => '9900',
            self::MIN_AMOUNT => 1600000
        ],
        9  => [
            self::AMOUNT => '9900',
            self::MIN_AMOUNT => 1100000
        ],
        12 => [
            self::AMOUNT => '9900',
            self::MIN_AMOUNT => 850000
        ],
        18 => [
            self::AMOUNT => '19900',
            self::MIN_AMOUNT => 1750000
        ],
        24 => [
            self::AMOUNT => '19900',
            self::MIN_AMOUNT => 1600000
        ],
    ];

    //processing fee flag
    const PROCESSING_FEES_FLAG = [
        3  => ' ',
        6  => 'A',
        9  => 'A',
        12 => 'A',
        18 => 'A',
        24 => 'A',
    ];
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

        $emiPaymentsForBank = $this->repo
                                   ->payment
                                   ->fetchEmiPaymentsWithRelationsBetween(
                                        $begin,
                                        $end,
                                        static::BANK_CODE,
                                        [
                                           'card.globalCard',
                                           'emiPlan',
                                           'merchant.merchantDetail',
                                           'terminal'
                                        ]);

        return $emiPaymentsForBank;
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
                    if ($emiPayment->terminal->isOptimizer())
                    {
                        continue;
                    }

                    $emiPlan = $emiPayment->emiPlan;

                    $gateway = $emiPayment->terminal->getGateway();

                    $merchantDetail = $emiPayment->merchant->merchantDetail;

                    $terminals = $this->repo->terminal->getActiveTerminalsBasedOnMethodsAndGateways(
                        $emiPayment->getMerchantId(), [],
                        [Gateway::EMI_SBI]
                    );

                    $terminalsByGateway = $this->getTerminalsByGateway($terminals);

                    $terminal = $gateway === 'hdfc' ? $terminalsByGateway[$gateway] : $terminalsByGateway['hitachi'];

                    if ($terminal === null) {
                        throw new LogicException(
                            'No SBI MID found for merchant',
                            null,
                            [
                                'gateway' => 'emi_sbi',
                                'gateway_file' => $this->gatewayFile->getId(),
                                'payment_id' => $emiPayment['id'],
                                'merchant_id' => $merchantDetail[Detail\Entity::MERCHANT_ID],
                            ]);
                    }

                    $mid = $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

                    $tid = $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];
                    if ($mid === null or
                        $tid === null) {
                        throw new LogicException(
                            'MID and TID can not be null',
                            null,
                            [
                                'gateway' => 'emi_sbi',
                                'payment_id' => $emiPayment['id'],
                                'merchant_id' => $merchantDetail[Detail\Entity::MERCHANT_ID],
                                'terminal_id' => $terminal->getId(),
                            ]);
                    }

                    $totalTransactions++;
                    $uniqueReferenceNum++;

                    try {
                        $redisKey = sprintf(self::REDIS_KEY_FMT, $emiPayment->getId());

                        $this->cache->set($redisKey, $uniqueReferenceNum, self::REDIS_KEY_TTL);

                        $this->trace->info(
                            TraceCode::SBI_EMI_FILE_UNIQUE_REFERENCE_NUM,
                            [
                                'payment_id'              => $emiPayment['id'],
                                'unique_reference_num'     => $uniqueReferenceNum,
                            ]
                        );

                    } catch (\Exception $e) {
                        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['cache_val_set_error' => $uniqueReferenceNum]);
                    }

                    $principalAmount = $emiPayment->getAmount();

                    $rate = self::getEmiRate($emiPayment, $emiPlan);

                    $rate_in_perc = $rate / 100;

                    $tenure = $emiPlan->getDuration();

                    $processingFees = $this->getProcessingFee($principalAmount, $tenure);

                    $businessName = $this->getBusinessName($merchantDetail);

                    $emiAmount = $this->getEmiAmount($principalAmount, $rate_in_perc, $tenure);

                    $card = $emiPayment->card;

                    if (isset($emiPayment->card->trivia) && isset($emiPayment->token))
                    {
                        $card = $emiPayment->token->card;
                    }

                    $skuPrefix = self::SKU_PREFIX_V1;

                    if ($this->isTerminalWhitelisted($terminal[Terminal\Entity::ID]) or
                        $this->isTerminalWithV2Sku($terminal))
                    {
                        $skuPrefix = self::SKU_PREFIX_V2;
                    }

                $body[] =
                    'DD' .    // record type always DD
                    'R' . $this->numpad($uniqueReferenceNum, 14) .
                    $this->strpad('Razor Pay', 40) .
                    $this->numpad($card->getLast4(), 19) .
                    $this->numpad($principalAmount, 17) .
                    $this->numpad($tenure, 3) .
                    $this->strpad($this->getAuthCode($emiPayment), 6) .
                    Carbon::createFromTimestamp($emiPayment['authorized_at'], Timezone::IST)->format('dmY') .
                    $this->strpad('Razor Pay', 40) .
                    $this->numpad($mid, 16) .
                    $this->strpad($businessName, 40) .
                    $this->strpad($tid, 8) .
                    str_pad($rate, 7, '0', STR_PAD_RIGHT) .
                    $this->strpad('', 40) .
                    $this->numpad($principalAmount, 17) .
                    'F' .
                    '0' .
                    self::PROCESSING_FEES_FLAG[$tenure] .
                    $this->numpad($processingFees, 7) .
                    $this->strpad($skuPrefix . substr($mid, -4), 20) .
                    $this->numpad('0', 17) .
                    $this->numpad($emiAmount, 17) .
                    $this->strpad('', 108);

                    $rowLength = strlen(end($body));

                    if ($rowLength !== 450) {
                        throw new LogicException(
                            'Row not formatted properly',
                            null,
                            [
                                'gateway' => 'emi_sbi',
                                'length' => $rowLength,
                                'payment_id' => $emiPayment['id'],
                            ]);
                    }

                    $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                        [
                            'payment_id' => $emiPayment->getId(),
                            'bank'       => static::BANK_CODE,
                        ]
                    );
                    // If a row is not added in the file, then that row's principal amount
                    // must not be added to the total amount
                    $totalAmount = $totalAmount + $principalAmount;
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
            $this->numpad($totalAmount, 17) .
            'F' .
            $this->strpad('', 411)
        ];

        $textRows = array_merge($header, $body);

        return implode("\r\n", $textRows);
    }

    protected function getProcessingFee($amount, $tenure)
    {
        $feePlan = self::PROCESSING_FEES[$tenure];

        $processingFees = '0000';

        if(isset($feePlan[self::MIN_AMOUNT]) === false or $amount > $feePlan[self::MIN_AMOUNT])
        {
            $processingFees = $feePlan[self::AMOUNT];
        }

        return $processingFees;
    }

    protected function getTerminalsByGateway($terminals)
    {
        $terminalsByGateway = [];
        foreach ($terminals as $terminal)
        {
            $gatewayAcquirer = $terminal->getGatewayAcquirer() === null ? 'hitachi' : $terminal->getGatewayAcquirer();
            $terminalsByGateway[$gatewayAcquirer] = $terminal;
        }
        return $terminalsByGateway;
    }

    protected function isTerminalWithV2Sku($terminal): bool
    {
        return $terminal[Terminal\Entity::CREATED_AT] > self::SKU_V2_START_TIMESTAMP;
    }

    protected function isTerminalWhitelisted($terminalId): bool {

        try {
            $properties = [
                "id" => $terminalId,
                "experiment_id" => $this->app['config']->get('app.sbi_sku_v2_migration_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'tid' => $terminalId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variables = $response['response']['variant']['variables'];

            foreach ($variables as $variable) {

                if ($variable['key'] == "result" && $variable['value'] == "on")
                {
                    return true;
                }

            }

        } catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SBI_SKU_V2_MIGRATION_SPLITZ_ERROR
            );
        }
        return false;
    }

    protected function getEmiRate($emiPayment, $emiPlan)
    {
        $tenure = $emiPlan->getDuration();

        if($emiPayment['authorized_at'] >= self::EMI_RATES_CHANGE_START_TIMESTAMP)
        {
            // new emi rates
            $rate = $emiPlan->getRate();
        }
        else
        {
            // old emi rates
            if($tenure == 18 or $tenure == 24)
            {
                $rate = self::OLD_EMI_RATES[$tenure];
            }
            else
            {
                // no change in rates for tenures other than 18 & 24
                $rate = $emiPlan->getRate();
            }
        }
        return $rate;
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
            'SBI',
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

        $count = $this->repo->gateway_file->fetchFileSentCountFromStart(Type::EMI, GatewayFileConstants::SBI, $start);

        return static::FILE_NAME . (string)($count + 1) . Carbon::now()->setTimezone(Timezone::IST)->format('YmdHis');
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

        // Replace characters from 57 till 76 which represents card numbers
        for ($i = 1; $i < sizeof($data); $i++)
        {
            $out[] = substr_replace($data[$i], '000000000000000', 57, 15);
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
