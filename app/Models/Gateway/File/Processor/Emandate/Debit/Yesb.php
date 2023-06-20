<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Customer\Token;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Enach\Yesb\FieldsLength;
use RZP\Gateway\Enach\Yesb\HeadingsLength;
use RZP\Gateway\Enach\Yesb\Fields as Fields;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Gateway\Enach\Yesb\DebitFileHeadings as Headings;

class Yesb extends Base
{
    const ACQUIRER = Payment\Gateway::ACQUIRER_YESB;

    const GATEWAY = Payment\Gateway::ENACH_NPCI_NETBANKING;

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::ENACH_NPCI_NB_DEBIT;

    const BASE_STORAGE_DIRECTORY = 'Npci/Enach/Netbanking/';

    const FILE_NAME = 'yesbank/nach/input_file/NACH_DR_{$date}_{$utilityCode}_RAZORPAY_{$serialNumber}';

    protected $fileStore;

    const STEP = 'debit';

    const FILE_CACHE_KEY = 'enach_yesb_gateway_file_index';

    const EMANDATE_NARRATION = 'emandate_narration';

    const FILE_METADATA = [
        'gid' => '10000',
        'uid' => '10004',
        'mode' => '33188'
    ];

    protected $pageCount   = 90000;

    protected $userName    = 'RAZORPAYSOFTWAREPRIVATELTD';

    protected $productType = '10 ';

    const NORMAL_TYPE = "normal";

    const MUT_TYPE = "mut";

    const EARLY_TYPE = "early";


    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;
    }

    /**
     * @throws GatewayFileException
     * code check: done
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->newFetchPendingEMandateDebitWithGatewayAcquirer(
                static::GATEWAY,
                $begin,
                $end,
                Payment\Gateway::ACQUIRER_YESB);
        }
        catch (ServerErrorException $e)
        {
            $this->generateMetric(Metric::EMANDATE_DB_ERROR);

            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        foreach ($tokens as $key => $token)
        {
            if ($token->merchant->isEarlyMandatePresentmentEnabled() === true)
            {
                unset($tokens[$key]);
            }
        }

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'begin'           => $begin,
                'end'             => $end,
                'count'           => count($paymentIds),
                'target' => $this->gatewayFile->getTarget(),
                'type'   => $this->gatewayFile->getType()
            ]);

        return $tokens;
    }

    /**
     * @param $data
     * @throws GatewayFileException
     * code check: done
     */
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
                $this->pageCount = 10;
            }

            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                $totalCount = count($fileData);

                $presentCount = 0;

                $serialNumber = 1;

                $this->getCacheKeySerialNumber($serialNumber, $cacheKey, $key);

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

            $this->trace->info(
                TraceCode::EMANDATE_DEBIT_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            // Need to add once instrument changes are done
            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "GEN_YES");
        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_GENERATION_ERROR);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ],
                $e);
        }
    }

    /**
     * @param $records
     * @return array
     * code check: done
     */
    protected function formatDataForFile($records): array
    {
        $rows = [];

        foreach ($records as $record)
        {
            $paymentId = $record['payment_id'];

            $utilityCode = $record->terminal->getGatewayMerchantId2();

            list($data, $isValid) = $this->getDebitData($record, $paymentId);

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
                    Headings::SPONSOR_BANK_IFSC                => $data[Fields::SPONSOR_BANK],
                    Headings::USER_NUMBER                      => $data[Fields::UTILITY_CODE],
                    Headings::TRANSACTION_REFERENCE            => $data[Fields::TRANSACTION_REFERENCE],
                    Headings::PRODUCT_TYPE                     => $this->productType,
                    Headings::BENEFICIARY_AADHAAR_NUMBER       => Fields::BENEFICIARY_AADHAAR_NUMBER,
                    Headings::UMRN                             => $data[Fields::UMRN],
                    Headings::FILLER                           => Fields::FILLER,
                ];

                $rows[$utilityCode][] = $row;
            }
            else
            {
                $this->trace->warning(
                    TraceCode::EMANDATE_DEBIT_REQUEST_ERROR,
                    [
                        'payment_id' => $paymentId,
                    ]
                );
            }
        }

        return $rows;
    }

    /**
     * @param array $data
     * @return string
     * code check: done
     */
    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = $this->getDate();

        $formatSerialNumber = $this->formatSerialNumber($data['serialNumber']);

        $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$serialNumber}' => $formatSerialNumber]);

        if (isset($data['utilityCode']) === true)
        {
            $fileName = strtr($fileName, ['{$utilityCode}' => $data['utilityCode']]);
        }

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return self::BASE_STORAGE_DIRECTORY . $fileName;
    }

    /**
     * @param $serialNumber
     * @return string
     * code check: done
     */
    protected function formatSerialNumber($serialNumber): string
    {
        return str_pad($serialNumber, 3, "0", STR_PAD_LEFT);
    }

    /**
     * @param string $key
     * @param array $fileData
     * @return array
     * code check: done
     */
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

    /**
     * @param Token\Entity $token
     * @param string $paymentId
     * @return array
     * code check: done
     */
    public function getDebitData(Token\Entity $token, string $paymentId): array
    {
        $isValid = $this->validateData($token);

        if ($isValid === false)
        {
            return ["", false];
        }

        $narration = $this->getNarration($token);

        $accountTypeValue = $this->getAccountTypeValue($token);

        $accountName = $token->getBeneficiaryName();
        $fieldLength = FieldsLength::BENEFICIARY_ACCOUNT_HOLDER_NAME;
        $accountName = $this->getPaddedValue($accountName, $fieldLength, ' ', STR_PAD_RIGHT);

        // Need to pass Merchant details in case Narration or terminal DBA is empty
        $userName = $this->getUserName($token, $narration);
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

        if($narration != null)
        {
            $merchantName = $this->getPaddedValue($narration, 15, 'X', STR_PAD_RIGHT);
        }
        else
        {
            $label = $token->merchant->getFilteredDba();
            $label = preg_replace('/\s+/', '', $label);
            $merchantName = $this->getPaddedValue($label, 10, 'X', STR_PAD_RIGHT);
        }

        $merchantName = strtoupper($merchantName);
        $transactionReference = implode("", [$merchantName, $paymentId]);
        $size = FieldsLength::TRANSACTION_REFERENCE;
        $transactionReference = $this->getPaddedValue($transactionReference, $size, ' ', STR_PAD_RIGHT);

        $sponsorBank = $token->terminal->getGatewayAccessCode();
        $size = FieldsLength::SPONSOR_BANK_IFSC;
        $sponsorBank = $this->getPaddedValue($sponsorBank, $size, ' ', STR_PAD_RIGHT);

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
            Fields::SPONSOR_BANK             => $sponsorBank,
        ], true];
    }

    /**
     * @param $token
     * @param $narration
     * @return string
     * code check: done
     */
    public function getUserName($token, $narration)
    {
        if($narration != null)
        {
            return strtoupper($narration);
        }

        $utilityCode = $token->terminal->getGatewayMerchantId2();

        $merchantName = $token->terminal->merchant->getFilteredDba();

        if($utilityCode !== null and
            $utilityCode !== "NACH00000000013149" and
            $merchantName !== null)
        {
            return strtoupper(preg_replace("/[^a-zA-Z0-9]/", "", $merchantName));
        }

        return $this->userName;
    }

    /**
     * @param $token
     * @return mixed|string|string[]|null
     * code check: done
     */
    public function getNarration($token)
    {
        try
        {
            $paymentNotes = $token['payment_notes'];

            if($paymentNotes != null and
                is_string($paymentNotes) === true)
            {
                $notesArray = json_decode($paymentNotes, true);

                if(is_array($notesArray) === true and
                    empty($notesArray[self::EMANDATE_NARRATION]) === false and
                    $notesArray[self::EMANDATE_NARRATION] !== null and
                    is_string($notesArray[self::EMANDATE_NARRATION]) === true)
                {
                    return preg_replace("/[^a-zA-Z0-9]/", "", $notesArray[self::EMANDATE_NARRATION]);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::EMANDATE_NARRATION_ERROR, ["narration parsing error" => $e]);
        }

        return null;
    }

    /**
     * @param $value
     * @param $fieldLength
     * @param $padString
     * @param $padType
     * @return false|string
     * code check: done
     */
    public function getPaddedValue($value, $fieldLength, $padString, $padType)
    {
        $size = $fieldLength;

        $pad_str = str_pad($value, $size, $padString, $padType);

        return substr($pad_str, 0, $size);
    }

    /**
     * @param $data
     * @param string $prependLine
     * @param string $glue
     * @return string
     * code check: done
     */
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

    /**
     * @param $data
     * @param string $glue
     * @param false $ignoreLastNewline
     * @return string
     * code check: done
     */
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
     * @param Token\Entity $token
     * @return bool
     * code check: done
     * check to find if gateway token/ utility code is 20
     */
    protected function validateData(Token\Entity $token): bool
    {
        if (strlen($token->getGatewayToken()) !== 20)
        {
            return false;
        }

        return true;
    }

    /**
     * @param Token\Entity $token
     * @return string
     * code check: done
     * Need to move to base file, if required
     */
    public function getAccountTypeValue(Token\Entity $token): string
    {
        $accountTypeMap = [
            Token\Entity::ACCOUNT_TYPE_SAVINGS     => '10',
            Token\Entity::ACCOUNT_TYPE_CURRENT     => '11',
            Token\Entity::ACCOUNT_TYPE_CASH_CREDIT => '13',
            Token\Entity::ACCOUNT_TYPE_SB_NRE      => '10',
            Token\Entity::ACCOUNT_TYPE_SB_NRO      => '10',
        ];

        $accountType = $token->getAccountType() ?? 'savings';

        return $accountTypeMap[$accountType];
    }

    /**
     * @param $serialNumber
     * @param $cacheKey
     * code check: done
     * @param $utilityCode
     */
    protected function getCacheKeySerialNumber(& $serialNumber, & $cacheKey, $utilityCode)
    {
        if($this->gatewayFile->getTarget() === Constants::YESB_EARLY_DEBIT)
        {
            $type = self::MUT_TYPE;
        }
        else
        {
            $type = self::NORMAL_TYPE;
        }

        $cacheKey = $this->getCacheKeyForFileNumber($type, $utilityCode);

        $serialNumberFromCache = $this->cache->get($cacheKey);

        $ttl = 60 * 60 * 24; // seconds

        if(isset($serialNumberFromCache) === true)
        {
            $serialNumber = $serialNumberFromCache;
        }
        else {
            $this->cache->put($cacheKey, $serialNumber, $ttl);
        }

        $this->trace->info(TraceCode::CACHE_KEY_GET, [
            'key'    => $cacheKey,
            'serialNumberFromCache' => $serialNumberFromCache,
            'resultSerialNumber' => $serialNumber,
        ]);
    }

    /**
     * @param $type
     * @param $utilityCode
     * @return string
     * code check: done
     */
    protected function getCacheKeyForFileNumber($type, $utilityCode): string
    {
        $date = $this->getDate();

        return self::FILE_CACHE_KEY . "_" . $utilityCode . "_" . $this->mode . "_" . $date . "_" . $type;
    }

    /**
     * @return string
     * code check: done
     */
    protected function getHeaderDate(): string
    {
        $offset = (int) $this->gatewayFile->getSubType();

        return Carbon::now(Timezone::IST)->addDays($offset)->format('dmY');
    }

    /**
     * @return string
     * code check: done.
     */
    protected function getDate(): string
    {
        $offset = (int) $this->gatewayFile->getSubType();
        
        return Carbon::now(Timezone::IST)->addDays($offset)->format('dmY');
    }

    /**
     * @param $data
     * @throws GatewayErrorException
     * code check: done
     */
    public function sendFile($data)
    {
        // for retry this step will fail anyway, as we don't have file store enttiy here
        if($this->fileStore === null)
        {
            return;
        }

        $files = $this->gatewayFile
            ->files()
            ->whereIn(FileStore\Entity::ID, $this->fileStore)
            ->get();

        $this->sendFilesInBatches($files, 1);
    }

    /**
     * @param $fileInfo
     * @param int $batch_size
     * @throws GatewayErrorException
     * code check: done
     * converting entire files into batches
     */
    protected function sendFilesInBatches($fileInfo, $batch_size = 1)
    {
        $pendingBatches = $fileInfo->chunk($batch_size);

        $sentFiles = $failedFiles = $timeoutFiles = [];

        foreach($pendingBatches as $pendingBatch)
        {
            $response = $this->sendEachFileBatch($pendingBatch);

            $sentFiles = array_merge($sentFiles, $response['sent_files']);

            $failedFiles = array_merge($failedFiles, $response['failed_files']);

            $timeoutFiles = array_merge($timeoutFiles, $response['timeout_files']);
        }

        $response = [
            'gateway_id' => $this->gatewayFile->getId(),
            'target' => $this->gatewayFile->getTarget(),
            'type'   => $this->gatewayFile->getType(),
            "failed_files"  => $failedFiles,
            "sent_files"    => $sentFiles,
            "timeout_files" => $timeoutFiles
        ];

        if(count($sentFiles) !== count($fileInfo))
        {
            $this->trace->info(
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
            
            $this->generateMetric(Metric::EMANDATE_BEAM_ERROR);

            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                $response
            );
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_BEAM_FILES_STATUS, [ $response ]);
    }

    /**
     * @param $pendingFiles
     * @return array
     * code check: done
     * sending files in batches, and updating file store once files are sent
     */
    protected function sendEachFileBatch($pendingFiles)
    {
        $sentFiles = $failedFiles = $timeoutFiles = [];

        $this->trace->info(TraceCode::GATEWAY_FILE_BEAM_FILES_PENDING,
            [
                "pendingFiles" => $this->getFileNames($pendingFiles),
                'gateway' => $this->gatewayFile->getTarget()
            ]);

        $this->filterFiles($pendingFiles, $sentFiles,Constants::FILE_SENT);

        $this->filterFiles($pendingFiles, $timeoutFiles, Constants::FILE_TIMEOUT);

        $this->filterFiles($pendingFiles, $timeoutFiles, Constants::FILE_UNKNOWN);

        $this->trace->info(TraceCode::GATEWAY_FILE_BEAM_FILES_FILTERED,
            [
                "pendingFiles" => $this->getFileNames($pendingFiles),
                'gateway' => $this->gatewayFile->getTarget()
            ]);

        if(count($pendingFiles) > 0)
        {
            $beamFiles = $this->getFileNames($pendingFiles);

            $beamResponse = $this->beamPushRequest($beamFiles);

            $this->trace->info(TraceCode::GATEWAY_FILE_BEAM_RESPONSE,
                [
                    "beam_response" => $beamResponse,
                    'gateway' => $this->gatewayFile->getTarget()
                ]);

            if($beamResponse !== null and
                isset($beamResponse['failed']) === true or
                isset($beamResponse['success']) === true)
            {
                $beamSuccessFiles = $beamResponse['success'] ?? [];

                foreach ($pendingFiles as $pendingFile)
                {
                    $pendingFileName = $this->getSingleFileName($pendingFile);

                    if(in_array($pendingFileName, $beamSuccessFiles))
                    {
                        $this->setFilesBeamStatus([$pendingFile], Constants::FILE_SENT);

                        array_push($sentFiles, $pendingFileName);
                    }
                    else
                    {
                        $this->setFilesBeamStatus([$pendingFile], Constants::FILE_FAILED);

                        array_push($failedFiles, $pendingFileName);
                    }
                }
            }
            else
            {
                if($beamResponse === null)
                {
                    $timeoutFiles = array_merge($timeoutFiles, $beamFiles);

                    $this->setFilesBeamStatus($pendingFiles, Constants::FILE_TIMEOUT);
                }
                else
                {
                    $timeoutFiles = array_merge($timeoutFiles, $beamFiles);

                    $this->setFilesBeamStatus($pendingFiles, Constants::FILE_UNKNOWN);
                }
            }
        }

        return [
            "failed_files"  => $failedFiles,
            "sent_files"    => $sentFiles,
            "timeout_files" => $timeoutFiles
        ];
    }

    /**
     * @param $beamFiles
     * @return mixed
     * code check: done
     * sending files to beam push request, to send it to bank SFTP server
     */
    protected function beamPushRequest($beamFiles)
    {
        $bucketConfig = $this->getBucketConfig(FileStore\Type::ENACH_NPCI_NB_DEBIT);

        $data =  [
            BeamService::BEAM_PUSH_FILES         => $beamFiles,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::YESBANK_ENACH_NB_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $beamFiles,
            'channel'   => 'emandate',
            'filetype'  => FileStore\Type::ENACH_NPCI_NB_DEBIT,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        return $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);
    }

    /**
     * code check: done
     */
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('16384M'); // 16 GB
    
        RuntimeManager::setTimeLimit(7200);
    
        RuntimeManager::setMaxExecTime(7200);
    }

}
