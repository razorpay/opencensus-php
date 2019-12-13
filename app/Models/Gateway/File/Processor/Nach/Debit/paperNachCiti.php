<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Carbon\Carbon;
use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Enach\Citi\FieldsLength;
use RZP\Gateway\Enach\Citi\HeadingsLength;
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
    const SUMMARY_FILE_NAME = 'citi/nach/RAZORP_SUMMARY_{$date}';
    const SUMMARY_EXTENSION = FileStore\Format::XLS;
    const STEP              = 'debit';
    const REFERENCE_PREFIX  = 'CTTATAAIAA';
    const FILE_METADATA     = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];

    protected $fileStore;

    protected $gateway  = Payment\Gateway::NACH_CITI;

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
            $summaryData = [];

            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                $fileHeader = $this->getFileHeader($key, $fileData);

                $fileHeaderText = $this->getTextData($fileHeader, "", "");

                $fileDataText   = $this->getTextData($fileData, $fileHeaderText, "");

                $fileName = $this->getFileToWriteNameWithoutExt(['fileName' =>static::FILE_NAME, 'utilityCode' => $key]);

                $creator = new FileStore\Creator;

                $amount = 0;

                foreach ($fileData as $data)
                {
                    $amount = $amount + $data[Headings::AMOUNT];
                }

                $size = count($fileData);

                $date = Carbon::now(Timezone::IST)->format('dmY');

                $row =[
                    Headings::UTILITY_CODE                  => $key,
                    Headings::NO_OF_RECORDS                 => $size,
                    Headings::TOTAL_AMOUNT                  => $amount,
                    Headings::SETTLEMENT_DATE               => $date,
                ];

                $summaryData[] = $row;

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
            }

            $creatorSummary = new FileStore\Creator;

            $summaryFileName = $this->getFileToWriteNameWithoutExt(['fileName' =>static::SUMMARY_FILE_NAME]);

            $creatorSummary->extension(static::SUMMARY_EXTENSION)
                           ->content($summaryData)
                           ->name($summaryFileName)
                           ->store(FileStore\Store::S3)
                           ->type(static::SUMMARY_FILE_TYPE)
                           ->entity($this->gatewayFile)
                           ->metadata(static::FILE_METADATA)
                           ->save();

            $file = $creatorSummary->getFileInstance();

            $fileStoreIds[] = $file->getId();

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

    protected function formatDataForFile($tokens)
    {
        $rows = [] ;

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $utilityCode = $token->terminal->getGatewayMerchantId();

            $data = $this->getNachDebitData($token, $paymentId);

             $row=[
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

        $data = [
            BeamService::BEAM_PUSH_FILES   => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::CITIBANK_NACH_FILE_JOB_NAME
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::CITI_NACH_DEBIT,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::NACH]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);

    }

    protected function getFileToWriteNameWithoutExt(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

         if (isset($data['utilityCode']) === true)
         {
            $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$utilityCode}' => $data['utilityCode']]);
         }
         else
         {
             $fileName = strtr($data['fileName'], ['{$date}' => $date]);
         }

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getFileHeader(string $key, array $fileData)
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

        $userName = $token->terminal->getGatewayMerchantId2();
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


        $utilityCode  = $token->terminal->getGatewayMerchantId();
        $size = FieldsLength::USER_NUMBER;
        $utilityCode = $this->getPaddedValue($utilityCode, $size, ' ', STR_PAD_RIGHT);

        $transactionReference = implode("", [self::REFERENCE_PREFIX, $paymentId]);
        $size = FieldsLength::TRANSACTION_REFERENCE;
        $transactionReference = $this->getPaddedValue($transactionReference, $size,
                                                 ' ', STR_PAD_RIGHT);

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
            Fields::UMRN                     => $UMRN,
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

    public function getAccountTypeValue(Token\Entity $token)
    {
        $row = [
            Fields::SAVINGS => '10',
            Fields::CURRENT => '11',
            ];

        $accountType  = $token->getAccountType() ?? 'savings' ;

        $accountTypeValue  = $row[$accountType];

        return $accountTypeValue;
    }

    public function getTextData($data, $prependLine = '', string $glue = '|')
    {
        $ignoreLastNewline = true;

        if ($prependLine === '')
        {
            $ignoreLastNewline = false;
        }

        $txt = $this->generateText($data, $glue, $ignoreLastNewline);

        return $prependLine . $txt;
    }

    public function generateText($data, $glue = '|', $ignoreLastNewline = false)
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            if (($ignoreLastNewline === false) or
                ($ignoreLastNewline === true))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }
}
