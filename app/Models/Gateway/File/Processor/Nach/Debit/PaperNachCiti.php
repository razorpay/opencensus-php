<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Mail;
use Cache;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Customer\Token;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Enach\Citi\FieldsLength;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Enach\Citi\HeadingsLength;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use RZP\Gateway\Enach\Citi\Fields as Fields;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Models\Gateway\File\Processor\Nach\Debit;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class PaperNachCiti extends Debit\Base
{
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::CITI_NACH_DEBIT;
    const SUMMARY_FILE_TYPE = FileStore\Type::CITI_NACH_DEBIT_SUMMARY;
    const FILE_NAME         = 'citi/nach/RAZORP_COLLECT_{$utilityCode}_{$date}';
    const SUMMARY_FILE_NAME = 'citi/nach/RAZORP_SUMMARY_{$utilityCode}_{$date}';
    const SUMMARY_EXTENSION = FileStore\Format::XLS;
    const STEP              = 'debit';
    const GATEWAY           = Payment\Gateway::NACH_CITI;
    const FILE_METADATA     = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];
    const FILE_CACHE_KEY    = 'nach_citi_gateway_file_index';

    const CITI_NACH_DATE_SELECT = 'citi_nach_date_select';

    protected $pageCount   = 90000;
    protected $userName    = 'CTRAZORPAY';
    protected $productType = '10 ';

    protected $fileStore;

    public function __construct()
    {
        parent::__construct();
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }
        try
        {
            if ($this->isTestMode() === true)
            {
                $this->pageCount = 3;
            }

            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                $totalCount = count($fileData);

                $presentCount = $serialNumber = 0;

                // get serial no if key present for current date
                $cacheKey = $this->getCacheKeyForFileIndex($key);

                $serialNumberFromCache = $this->cache->get($cacheKey);

                if(isset($serialNumberFromCache) === true)
                {
                    $serialNumber = $serialNumberFromCache;
                }

                $this->trace->info(TraceCode::CACHE_KEY_GET, [
                    'key'    => $cacheKey,
                    'result' => $serialNumberFromCache,
                ]);

                while ($presentCount < $totalCount)
                {
                    if ($presentCount % 50000 === 0)
                    {
                        $this->trace->info(TraceCode::NACH_DEBIT_REQUEST, [
                            'index'        => $presentCount,
                            'utility_code' => $key,
                            'total_count'  => $totalCount,
                            'serialNumber' => $serialNumber,
                        ]);
                    }

                    $fileDataPerSheet = array_slice($fileData, $presentCount, $this->pageCount, true);

                    $fileHeader = $this->getFileHeader($key, $fileDataPerSheet);

                    $fileHeaderText = $this->getTextData($fileHeader, "", "");

                    $fileDataText = $this->getTextData($fileDataPerSheet, $fileHeaderText, "");

                    $fileName = $this->getFileToWriteNameWithoutExt(
                                [
                                    'fileName'     => static::FILE_NAME,
                                    'utilityCode'  => $key,
                                    'serialNumber' => $serialNumber,
                                ]);

                    $creator = new FileStore\Creator;

                    $creator->extension(static::EXTENSION)
                            ->content($fileDataText)
                            ->name($fileName)
                            ->store(FileStore\Store::S3)
                            ->type(static::FILE_TYPE)
                            ->entity($this->gatewayFile)
                            ->metadata(static::FILE_METADATA)
                            ->save();

                    $file = $creator->getFileInstance();

                    $fileStoreIds[] = $file->getId();

                    $amount = 0;

                    foreach ($fileDataPerSheet as $data)
                    {
                        $amount = $amount + $data[Headings::AMOUNT];
                    }

                    $date = $this->getHeaderDate();

                    $summaryRow = [
                        0 => [
                            Headings::UTILITY_CODE    => $key,
                            Headings::NO_OF_RECORDS   => count($fileDataPerSheet),
                            Headings::TOTAL_AMOUNT    => $amount,
                            Headings::SETTLEMENT_DATE => $date,
                        ]
                    ];

                    $summaryFileName = $this->getFileToWriteNameWithoutExt(
                                       [
                                           'fileName'     => static::SUMMARY_FILE_NAME,
                                           'utilityCode'  => $key,
                                           'serialNumber' => $serialNumber,
                                       ]);

                    $creatorSummary = new FileStore\Creator;

                    $creatorSummary->extension(static::SUMMARY_EXTENSION)
                                   ->content($summaryRow)
                                   ->name($summaryFileName)
                                   ->store(FileStore\Store::S3)
                                   ->type(static::SUMMARY_FILE_TYPE)
                                   ->entity($this->gatewayFile)
                                   ->metadata(static::FILE_METADATA)
                                   ->save();

                    $file = $creatorSummary->getFileInstance();

                    $fileStoreIds[] = $file->getId();

                    // cache index of utility code for 24hours (in seconds)
                    $serialNumber = $this->cache->increment($cacheKey);

                    $this->trace->info(TraceCode::CACHE_KEY_SET, [
                        'key'    => $cacheKey,
                        'result' => $serialNumber,
                    ]);

                    $presentCount = $presentCount + $this->pageCount;
                }
            }

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [] ;

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $utilityCode = $token->terminal->getGatewayMerchantId2();

            list($data, $isValid) = $this->getNachDebitData($token, $paymentId);

            if ($isValid === true)
            {
                $row = [
                    Headings::ACH_TRANSACTION_CODE             => Fields::ACH_TRANSACTION_CODE,
                    Headings::CONTROL_9S                       => Fields::CONTROL_9,
                    Headings::DESTINATION_ACCOUNT_TYPE         => $data[Fields::ACCOUNT_TYPE_VALUE],
                    Headings::LEDGER_FOLIO_NUMBER              => Fields::LEDGER_FOLIO_NUMBER,
                    Headings::CONTROL_15S                      => Fields::CONTROL_15,
                    Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  => $data[Fields::ACCOUNT_NAME],
                    Headings::CONTROL_9SS                      => Fields::CONTROL_9,
                    Headings::CONTROL_7S                       => Fields::CONTROL_7,
                    Headings::USER_NAME                        => $data[Fields::USERNAME],
                    Headings::CONTROL_13S                      => Fields::CONTROL_13,
                    Headings::AMOUNT                           => $data[Fields::AMOUNT],
                    Headings::ACH_ITEM_SEQ_NO                  => Fields::ACH_ITEM_SEQ_NUMBER,
                    Headings::CHECKSUM                         => Fields::CHECK_SUM,
                    Headings::FLAG                             => Fields::FLAG,
                    Headings::REASON_CODE                      => Fields::REASON_CODE,
                    Headings::DESTINATION_BANK_IFSC            => $data[Fields::IFSC],
                    Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  => $data[Fields::ACCOUNT_NUMBER],
                    Headings::SPONSOR_BANK_IFSC                => $data[Fields::SPONSER_BANK],
                    Headings::USER_NUMBER                      => $data[Fields::UTILITY_CODE],
                    Headings::TRANSACTION_REFERENCE            => $data[Fields::TRANSACTION_REFERENCE],
                    Headings::PRODUCT_TYPE                     => $this->productType,
                    Headings::BENEFICIARY_AADHAR_NUMBER        => Fields::BENEFICIARY_AADHAR_NUMBER,
                    Headings::UMRN                             => $data[Fields::UMRN],
                    Headings::FILLER                           => Fields::FILLER,
                ];

                $rows[$utilityCode][] = $row;
            }
            else
            {
                $this->trace->warning(
                    TraceCode::NACH_DEBIT_REQUEST_ERROR,
                    [
                        'payment_id' => $paymentId,
                    ]
                );
            }
        }

        return $rows;
    }

    /**
     * @throws GatewayErrorException
     */
    public function sendFile($data)
    {
        $fileInfo = [];

        $files = $this->gatewayFile
                      ->files()
                      ->whereIn(FileStore\Entity::ID, $this->fileStore)
                      ->get();

        foreach ($files as $file)
        {
            $fullFileName = $file->getName() . '.' . $file->getExtension();

            $fileInfo[] = $fullFileName;
        }

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::CITIBANK_NACH_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::CITI_NACH_DEBIT,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null) or
            ($beamResponse['failed'] !== null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'gateway_file' => $this->gatewayFile->getId(),
                    'target' => 'paper_nach_citi',
                ]
            );
        }

        $mailData = $this->formatDataForMail($files);

        $type = static::GATEWAY . '_' . static::STEP;
        $mailable = new NachMail($mailData, $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = $this->getDate();

         if (isset($data['utilityCode']) === true)
         {
            $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$utilityCode}' => $data['utilityCode']]);
         }
         else
         {
             $fileName = strtr($data['fileName'], ['{$date}' => $date]);
         }

         if ((isset($data['serialNumber']) === true) and ($data['serialNumber'] > 0))
         {
             $fileName = $fileName . '_' . $data['serialNumber'];
         }

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getFileHeader(string $key, array $fileData): array
    {
        $rows = [];

        $amount = 0;
        foreach ($fileData as $data)
        {
            $amount = $amount + $data['Amount'];

            $sponsorBank = $data[Headings::SPONSOR_BANK_IFSC];
        }

        $fieldLength = FieldsLength::AMOUNT;
        $amount = $this->getPaddedValue($amount, $fieldLength, '0', STR_PAD_LEFT);

        $size = count($fileData);
        $size = $this->getPaddedValue($size, HeadingsLength::TOTAL_ITEMS, '0', STR_PAD_LEFT);

        $length         = FieldsLength::USER_NUMBER;
        $utilityCode  = $this->getPaddedValue($key, $length, ' ', STR_PAD_RIGHT);

        $date = $this->getHeaderDate();

        $row = [
            Headings::ACH_TRANSACTION_CODE              => Fields::ACH_TRANSACTION_CODE_HEADING,
            Headings::CONTROL_7Z                        => Fields::CONTROL_7_HEADING,
            Headings::USER_NAME                         => Fields::USERNAME_HEADING,
            Headings::CONTROL_14Z                       => Fields::CONTROL_14_HEADING,
            Headings::ACH_FILE_NUMBER                   => Fields::ACH_FILE_NUMBER_HEADING,
            Headings::CONTROL_9S                        => Fields::CONTROL_9_HEADING,
            Headings::CONTROL_15S                       => Fields::CONTROL_15_HEADING,
            Headings::LEDGER_FOLIO_NUMBER               => Fields::LEDGER_FOLIO_NUMBER_HEADING,
            Headings::MAX_AMOUNT                        => Fields::USER_DEFINED_LIMIT_FOR_INDIVIDUAL_ITEMS,
            Headings::AMOUNT                            => $amount,
            Headings::SETTLEMENT_DATE                   => $date,
            Headings::ACH_ITEM_SEQ_NO                   => Fields::ACH_ITEM_SEQ_NUMBER_HEADING,
            Headings::CHECKSUM                          => Fields::CHECK_SUM_HEADING,
            Headings::FILLER_3                          => Fields::FILLER_3,
            Headings::UTILITY_CODE                      => $utilityCode,
            Headings::USER_NUMBER                       => Fields::USER_REFERENCE_HEADING,
            Headings::SPONSOR_BANK_IFSC                 => $sponsorBank,
            Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER   => Fields::USER_BANK_ACCOUNT_NUMBER_HEADING,
            Headings::SIZE                              => $size,
            Headings::SETTLEMENT_CYCLE                  => Fields::SETTLEMENT_CYCLE_HEADING,
            Headings::FILLER_57                         => Fields::FILLER_57,
        ];

        $rows[] = $row;

        return $rows;
    }

    public function getNachDebitData(Token\Entity $token, string $paymentId): array
    {
        $isValid = $this->validateData($token);

        if ($isValid === false)
        {
            return ["", false];
        }

        $accountTypeValue = $this->getAccountTypeValue($token);

        $accountName = $token->getBeneficiaryName();
        $fieldLength = FieldsLength::BENEFICIARY_ACCOUNT_HOLDER_NAME;
        $accountName = $this->getPaddedValue($accountName, $fieldLength, ' ', STR_PAD_RIGHT);

        $userName = $this->userName;
        $fieldLength = FieldsLength::USER_NAME;
        $userName = $this->getPaddedValue($userName, $fieldLength, ' ', STR_PAD_RIGHT);

        $amount = $token['payment_amount'];
        $fieldLength = FieldsLength::AMOUNT;
        $amount = $this->getPaddedValue($amount, $fieldLength, '0', STR_PAD_LEFT);

        $ifsc = $token->getIfsc();
        $fieldLength = FieldsLength::DESTINATION_BANK_IFSC;
        $ifsc = $this->getPaddedValue($ifsc, $fieldLength, ' ', STR_PAD_RIGHT);

        $accountNumber = $token->getAccountNumber();
        $fieldLength = FieldsLength::BENEFICIARY_BANK_ACCOUNT_NUMBER;
        $accountNumber = $this->getPaddedValue($accountNumber, $fieldLength, ' ', STR_PAD_RIGHT);

        $UMRN = $token->getGatewayToken();
        $size = FieldsLength::UMRN;
        $UMRN = $this->getPaddedValue($UMRN, $size, ' ', STR_PAD_RIGHT);

        $utilityCode  = $token->terminal->getGatewayMerchantId2();
        $size = FieldsLength::USER_NUMBER;
        $utilityCode = $this->getPaddedValue($utilityCode, $size, ' ', STR_PAD_RIGHT);

        $label = $token->merchant->getFilteredDba();
        $label = preg_replace('/\s+/', '', $label);
        $merchantName = $this->getPaddedValue($label, 10, 'X', STR_PAD_RIGHT);
        $merchantName = strtoupper($merchantName);
        $transactionReference = implode("", [$merchantName, $paymentId]);
        $size = FieldsLength::TRANSACTION_REFERENCE;
        $transactionReference = $this->getPaddedValue($transactionReference, $size,
                                                 ' ', STR_PAD_RIGHT);

        $sponserBank = $token->terminal->getGatewayAccessCode();
        $size = FieldsLength::SPONSER_BANK_IFSC;
        $sponserBank = $this->getPaddedValue($sponserBank, $size, ' ', STR_PAD_RIGHT);

        return [[
            Fields::ACCOUNT_TYPE_VALUE       => $accountTypeValue,
            Fields::ACCOUNT_NAME             => $accountName,
            Fields::USERNAME                 => $userName,
            Fields::AMOUNT                   => $amount,
            Fields::IFSC                     => $ifsc,
            Fields::ACCOUNT_NUMBER           => $accountNumber,
            Fields::UMRN                     => $UMRN,
            Fields::UTILITY_CODE             => $utilityCode,
            Fields::TRANSACTION_REFERENCE    => $transactionReference,
            Fields::SPONSER_BANK             => $sponserBank,
        ], true];
    }

    public function getPaddedValue($value, $fieldLength, $padString, $padType)
    {
        $size = $fieldLength;

        $pad_str = str_pad($value, $size, $padString, $padType);

        return substr($pad_str, 0, $size);
    }

    public function getTextData($data, $prependLine = '', string $glue = '|'): string
    {
        $ignoreLastNewline = true;

        if ($prependLine === '')
        {
            $ignoreLastNewline = false;
        }

        $txt = $this->generateText($data, $glue, $ignoreLastNewline);

        return $prependLine . $txt;
    }

    public function generateText($data, $glue = '|', $ignoreLastNewline = false): string
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            if (($ignoreLastNewline === false) or
                ($ignoreLastNewline === true))
            {
                $txt .= "\n";
            }
        }

        return $txt;
    }

    /**
     * @throws GatewayFileException
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                         ->addHours(9)
                         ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                       ->addHours(9)
                       ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingNachOrMandateDebit(
                        [Payment\Gateway::ENACH_NPCI_NETBANKING, Payment\Gateway::NACH_CITI],
                        $begin,
                        $end,
                        Payment\Gateway::ACQUIRER_CITI);
        }
        catch (ServerErrorException $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        foreach ($tokens as $key => $token)
        {
            $tokenBegin = $begin;

            if ($token->merchant->isEarlyMandatePresentmentEnabled() === true)
            {
                while ($tokenBegin < $end)
                {
                    $nineAM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(9);
                    $threePM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(15);
                    $createdAt = $token['payment_created_at'];
                    /*
                     * the payments done from previous day 9am to 3pm should not be considered here as
                     * these payments will be part of mutual fund exclusive timing cycle (9am to 3pm)
                     */
                    if (($createdAt >= $nineAM->timestamp) and ($createdAt < $threePM->timestamp))
                    {
                        unset($tokens[$key]);
                    }
                    $tokenBegin = $tokenBegin + Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;
                }
            }
        }

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'begin'           => $begin,
                'end'             => $end,
                'entity_count'    => count($paymentIds),
            ]);

        return $tokens;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('28672'); // 28GB

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }

    protected function validateData(Token\Entity $token): bool
    {
        if (strlen($token->getGatewayToken()) !== 20)
        {
            return false;
        }

        return true;
    }

    protected function getDate(): string
    {
        return Carbon::now(Timezone::IST)->format('dmY');
    }

    protected function getHeaderDate(): string
    {
        $variant = $this->app['razorx']->getTreatment(
            UniqueIdEntity::generateUniqueId(), self::CITI_NACH_DATE_SELECT, $this->app['basicauth']->getMode());

        if ($variant === 'on')
        {
            return Carbon::now(Timezone::IST)->addDay()->format('dmY');
        }

        return Carbon::now(Timezone::IST)->format('dmY');
    }

    protected function getCacheKeyForFileIndex($utilityCode): string
    {
        $date = Carbon::now(Timezone::IST)->startOfDay()->timestamp;

        return self::FILE_CACHE_KEY . "_" . $utilityCode . "_" . $this->mode . "_" . $date;
    }
}
