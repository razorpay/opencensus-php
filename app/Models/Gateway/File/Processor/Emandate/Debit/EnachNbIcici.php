<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Gateway\File\Status;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Gateway\EMandate\Base as EmandateMail;
use RZP\Models\Gateway\File\Processor\Emandate\Debit;
use RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank\FieldsLength;
use RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank\HeadingsLength;
use RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank\Fields as Fields;
use RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank\DebitFileHeadings as Headings;

class EnachNbIcici extends Debit\Base
{
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::ENACH_NPCI_NB_DEBIT_ICICI;
    const FILE_NAME              = 'icici/nach/debit/ACH-DR-ICIC-ICIC865719-{$date}-{$batchCode}-INP';
    const STEP                   = 'debit';
    const GATEWAY                = Payment\Gateway::ENACH_NPCI_NETBANKING;
    const USER_NAME              = 'RZP';
    const BASE_STORAGE_DIRECTORY = 'Icici/Enach/Netbanking/';

    // not required anymore. keeping for historical reasons
    const FILE_METADATA = [
        'gid'   => '10000',
        'uid'   => '10002',
        'mode'  => '33188'
    ];

    protected $fileStore;

    protected $mailData;

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;

        $this->mailData = [];
    }

    /**
     * @throws GatewayFileException
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
            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            $index = 0;

            foreach ($allFilesData as $key => $fileData)
            {
                $index++;

                $fileHeader = $this->getFileHeader($fileData);

                $fileHeaderText = $this->getTextData($fileHeader, "", "");

                $fileDataText   = $this->getTextData($fileData, $fileHeaderText, "");

                $fileName = $this->getFileToWriteNameWithoutExt(['fileName' => static::FILE_NAME, 'batchCode' => $index]);

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

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

                $fileNameForMail = basename($fileName) . '.' . self::EXTENSION;

                $this->mailData[$fileNameForMail] = $this->formatDataForMail($fileData);

                $this->mailData[$fileNameForMail]['sr_no'] = $index;
            }

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_DEBIT_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                ]);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "GEN_NB_ICICI");
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

            $data = $this->getNachDebitData($token, $paymentId);

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
                Headings::PRODUCT_TYPE                     => Fields::PRODUCT_TYPE,
                Headings::BENEFICIARY_AADHAR_NUMBER        => Fields::BENEFICIARY_AADHAR_NUMBER,
                Headings::UMRN                             => $data[Fields::UMRN],
                Headings::FILLER                           => Fields::FILLER,
            ];

            $rows[$utilityCode][] = $row;
        }

        return $rows;
    }

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

        $bucketConfig = $this->getBucketConfig(FileStore\Type::ENACH_NPCI_NB_DEBIT_ICICI);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::ICICI_ENACH_NB_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'emandate',
            'filetype'  => FileStore\Type::ENACH_NPCI_NB_DEBIT_ICICI,
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
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'target'        => 'enach_nb_icici',
                ]
            );
        }

        $type = Constants::ENACH_NB_ICICI . '_' . self::STEP;

        $mailable = new EmandateMail(['mailData' => $this->mailData], $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $batchNo = $this->getPaddedValue($data['batchCode'], 4, '0', STR_PAD_LEFT);

        $batchCode = 'RZ' . $batchNo;

        $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$batchCode}' => $batchCode]);

        return self::BASE_STORAGE_DIRECTORY . $fileName;
    }

    protected function getFileHeader(array $fileData): array
    {
        $rows = [];

        $amount = 0;

        foreach ($fileData as $data)
        {
            $amount = $amount + $data[Headings::AMOUNT];

            $sponsorBank = $data[Headings::SPONSOR_BANK_IFSC];

            $utilityCode = $data[Headings::USER_NUMBER];
        }

        $fieldLength = FieldsLength::AMOUNT;
        $amount      = $this->getPaddedValue($amount, $fieldLength, '0', STR_PAD_LEFT);

        $size = count($fileData);
        $size = $this->getPaddedValue($size, HeadingsLength::TOTAL_ITEMS, '0', STR_PAD_LEFT);

        $length         = FieldsLength::USER_NUMBER;
        $utilityCode    = $this->getPaddedValue($utilityCode, $length, ' ', STR_PAD_RIGHT);

        $date = Carbon::now(Timezone::IST)->format('dmY');

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

    public function getNachDebitData(
        Token\Entity $token,
        string $paymentId
    ): array
    {
        $accountTypeValue = $this->getAccountTypeValue($token);

        $accountName = $token->getBeneficiaryName();
        $fieldLength = FieldsLength::BENEFICIARY_ACCOUNT_HOLDER_NAME;
        $accountName = $this->getPaddedValue($accountName, $fieldLength, ' ', STR_PAD_RIGHT);

        $label = $token->merchant->getBillingLabel();
        $filteredLabel = preg_replace('/[^a-zA-Z0-9]+/', '', $label);
        $userName = self::USER_NAME . $filteredLabel;
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

        $umrn = $token->getGatewayToken();
        $size = FieldsLength::UMRN;
        $umrn = $this->getPaddedValue($umrn, $size, ' ', STR_PAD_RIGHT);


        $utilityCode  = $token->terminal->getGatewayMerchantId2();
        $size = FieldsLength::USER_NUMBER;
        $utilityCode = $this->getPaddedValue($utilityCode, $size, ' ', STR_PAD_RIGHT);

        $size = FieldsLength::TRANSACTION_REFERENCE;
        $transactionReference = $this->getPaddedValue($paymentId, $size, ' ', STR_PAD_RIGHT);

        $sponserBank = $token->terminal->getGatewayAccessCode();
        $size = FieldsLength::SPONSER_BANK_IFSC;
        $sponserBank = $this->getPaddedValue($sponserBank, $size, ' ', STR_PAD_RIGHT);


        return [
            Fields::ACCOUNT_TYPE_VALUE       => $accountTypeValue,
            Fields::ACCOUNT_NAME             => $accountName,
            Fields::USERNAME                 => $userName,
            Fields::AMOUNT                   => $amount,
            Fields::IFSC                     => $ifsc,
            Fields::ACCOUNT_NUMBER           => $accountNumber,
            Fields::UMRN                     => $umrn,
            Fields::UTILITY_CODE             => $utilityCode,
            Fields::TRANSACTION_REFERENCE    => $transactionReference,
            Fields::SPONSER_BANK             => $sponserBank,
        ];
    }

    public function getPaddedValue($value, $fieldLength, $padString, $padType)
    {
        $size = $fieldLength;

        $pad_str = str_pad($value, $size, $padString, $padType);

        return substr($pad_str, 0, $size);
    }

    public function getAccountTypeValue(Token\Entity $token): string
    {
        $row = [
            Fields::SAVINGS => '10',
            Fields::CURRENT => '11',
        ];

        $accountType  = $token->getAccountType() ?? 'savings' ;

        $accountTypeValue  = $row[$accountType];

        return $accountTypeValue;
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
        if (Holidays::isWorkingDay(Carbon::now(Timezone::IST)) === false)
        {
            return new PublicCollection();
        }

        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                         ->addHours(9)
                         ->getTimestamp();

        $begin = $this->getLastWorkingDay($begin);

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                       ->addHours(9)
                       ->getTimestamp();

        try
        {
            $tokens = $this->repo->token->fetchPendingEMandateDebitWithGatewayAcquirer(
                    static::GATEWAY,
                    $begin,
                    $end,
                    Payment\Gateway::ACQUIRER_ICIC);
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

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    protected function getNewGatewayPaymentEntity(): Enach\Base\Entity
    {
        return new Enach\Base\Entity;
    }

    protected function getGatewayAttributes($token): array
    {
        return [
            Enach\Base\Entity::ACQUIRER => Payment\Gateway::ACQUIRER_ICIC,
            Enach\Base\Entity::UMRN     => $token['gateway_token'],
        ];
    }

    protected function getLastWorkingDay($timestamp)
    {
        $date = (new Carbon())->timestamp($timestamp);

        while (Holidays::isWorkingDay($date) === false)
        {
            $date = $date->subDay();
        }

        return $date->timestamp;
    }

    protected function formatDataForMail($fileData): array
    {
        $amount = 0;

        $date = Carbon::now(Timezone::IST)->format('d/m/Y');

        foreach ($fileData as $row)
        {
            $amount = $amount + (int) $row[Headings::AMOUNT];
        }

        return [
            'amount' => number_format($amount / 100, 2, '.', ','),
            'count'  => count($fileData),
            'date'   => $date
        ];
    }
}
