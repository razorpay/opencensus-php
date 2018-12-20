<?php

namespace RZP\Models\FundTransfer\Axis2;

use Mail;
use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\FundTransfer\Mode;
use RZP\Encryption\PGPEncryption;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Settlement\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Axis2\Constants as Axis2Constants;

class NodalAccount extends NodalBase\FileProcessor
{
    use FileHandlerTrait;

    const SIGNED_URL_DURATION = '1440';

    const BEAM_FILE_TYPE      = 'settlement';

    protected $id;

    protected $emptyRow;

    protected $data = null;

    protected $encryptionKey  = null;

    public function __construct()
    {
        parent::__construct();

        $this->id         = Base\UniqueIdEntity::generateUniqueId();

        $this->emptyRow   = $this->getEmptyArray();

        $this->encryptionKey  = Config::get('nodal.axis2.axis2_nodal_pgp_encryption_key');

        $this->encryptionKey  = trim(str_replace('\n', "\n", $this->encryptionKey));
    }

    /**
     * Gives an array with all fields required for settlement file generation.
     * All the fields are set to null
     * Generated array will be in the order acceptable from the bank.
     *
     * @return array
     */
    protected function getEmptyArray(): array
    {
        $headings = Headings::getRequestFileHeadings();

        $count    = count($headings);

        return array_combine($headings, array_fill(0, $count, null));
    }

    public function generateFundTransferFile($entities, $h2h = true): FileStore\Creator
    {
        $records                  = $this->getRecords($entities);

        $this->trace->info(TraceCode::FTA_ROWS_FETCHED_FOR_FILE);

        $textContent              = $this->generateText($records, '^');

        list($axisFile, $rzpFile) = $this->createFile($textContent);

        $this->trace->info(TraceCode::FTA_FILE_CREATED_IN_S3);

        $fileData                 = $this->getFileData($rzpFile);

        $this->sendAxisTransferMail($fileData);

        $this->trace->info(TraceCode::FTA_FILE_EMAIL_SENT);

        //
        // Pushing to Beam after sending the email
        // such that current settlement processing
        // doesn't get affected by Beam errors.
        //
        $this->sendFile($axisFile);

        $this->trace->info(TraceCode::FTA_FILE_SEND_VIA_BEAM);

        return $axisFile;
    }

    protected function getRecords(Base\PublicCollection $entities): array
    {
        $records = [];

        $timeNow = Carbon::now(Timezone::IST)->format('Y-m-d');

        foreach ($entities as $entity)
        {
            $record   = $this->emptyRow;

            $source   = $entity->source;

            $amount   = ($source->getAmount() / 100);

            $ba       = $entity->bankAccount;

            $mode     = $this->getPaymentType($amount, $ba);

            $this->updateSummary($mode, $amount);

            $mode     = Axis2Constants::MODE_MAPPING[$mode];

            $beneName = $this->normalizeBeneficiaryName($ba->getBeneficiaryName());

            $record[Headings::IDENTIFIER]                 = Axis2Constants::IDENTIFIER;
            $record[Headings::PAYMENT_MODE]               = $mode;
            $record[Headings::CORPORATE_CODE]             = Axis2Constants::CORP_CODE;
            $record[Headings::CUSTOMER_REFERENCE_NUMBER]  = $entity->getId();
            $record[Headings::DEBIT_ACCOUNT_NUMBER]       = Axis2Constants::DEBIT_ACCOUNT_NUMBER;
            $record[Headings::VALUE_DATE]                 = $timeNow;
            $record[Headings::TRANSACTION_CURRENCY]       = Axis2Constants::CURRENCY;
            $record[Headings::TRANSACTION_AMOUNT]         = $amount;
            $record[Headings::BENEFICIARY_NAME]           = $beneName;
            $record[Headings::BENEFICIARY_CODE]           = $ba->getId();
            $record[Headings::BENEFICIARY_ACCOUNT_NUMBER] = $ba->getAccountNumber();
            $record[Headings::BENEFICIARY_IFSC_CODE]      = $ba->getIfscCode();

            $records[] = $record;
        }

        return $records;
    }

    protected function createFile(string $textContent): array
    {
        $fileName = 'axis/poweraccess/outgoing_settlement/' . $this->getFileId();

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($textContent)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->headers(false)
                        ->metadata($metadata)
                        ->encrypt(Type::PGP_ENCRYPTION,
                            [
                                PGPEncryption::PUBLIC_KEY  => $this->encryptionKey,
                                PGPEncryption::USE_ARMOR   => 1
                            ])
                        ->save();

        $creator = new FileStore\Creator;

        $rzpFile = $creator->extension(FileStore\Format::TXT)
                           ->content($textContent)
                           ->name($fileName)
                           ->store(FileStore\Store::S3)
                           ->type(FileStore\Type::FUND_TRANSFER_DEFAULT)
                           ->headers(false)
                           ->metadata($metadata)
                           ->save();

        return [$file, $rzpFile];
    }

    protected function getFileData($file)
    {
        $fileInstance  = $file->get();

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $fileInstance['local_file_path'],
            'file_name'  => basename($fileInstance['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData;
    }

    protected function getH2HMetadata()
    {
        // To be changed perhaps
        return [
            'gid'   => '10000',
            'uid'   => '10003',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }

    protected function getPaymentType($amount, BankAccount\Entity $ba)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === 'UTIB')
        {
            return Mode::IFT;
        }

        $mode = $this->getTransferMode($amount, $ba->merchant);

        return $mode;
    }

    protected function sendAxisTransferMail(array $fileData)
    {
        $data = [
            'channel'    => $this->channel,
            'summary'    => $this->summary,
            'file_data'  => $fileData,
            'body'       => json_encode($this->data, JSON_PRETTY_PRINT),
            'recipients' => 'axis.nodal.transfers@razorpay.com'
        ];

        $axisSettlementMail = new SettlementMail($data);

        Mail::queue($axisSettlementMail);
    }

    protected function getFileId(): string
    {
        $timeNow   = Carbon::now(Timezone::IST);

        $date      = $timeNow->format('dmY');

        $serialNum = $timeNow->format('hms');

        return Axis2Constants::CORP_CODE . '_H2H_' . $date . '_' . $serialNum;
    }

    /* Normalizes beneficiary name should have length of max 70
     * Allowed characters  a-z A-Z \s \d () , : . / -
     *
     * @param $name
     * @return string
     */
    protected function normalizeBeneficiaryName($name): string
    {
        if($name !== null)
        {
            $normalizedNameWithSpaces =  preg_replace('/[^a-zA-Z\/\-\:\(\).,\s\d]/', '', $name);

            $normalizedString = preg_replace('/\s+/',' ', $normalizedNameWithSpaces);

            return substr($normalizedString, 0, 70);
        }

        return $name;
    }

    protected function getTransferMode($amount, Merchant\Entity $merchant): string
    {
        $rtgsMinCutoffTime = Carbon::createFromTime(
            self::RTGS_CUTOFF_HOUR_MIN,
            0,
            0,
            Timezone::IST
        )->getTimestamp();

        $rtgsMaxCutoffTime = Carbon::createFromTime(
            self::RTGS_CUTOFF_HOUR_MAX,
            self::RTGS_CUTOFF_MINUTE_MAX,
            0,
            Timezone::IST)->getTimestamp();


        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $mode = Mode::NEFT;

        if ($amount < self::MAX_IMPS_AMOUNT)
        {
            $mode = Mode::IMPS;
        }

        if ((($now >= $rtgsMinCutoffTime) and ($now <= $rtgsMaxCutoffTime)) and
            ($amount >= self::MIN_RTGS_AMOUNT))
        {
            $mode = Mode::RTGS;
        }

        return $mode;
    }

    /**
     * @param FileStore\Creator $file
     * Send file to bank through Beam
     */
    protected function sendFile(FileStore\Creator $file)
    {
        $fileInfo = [$file->getFullFileName()];

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => BeamConstants::AXIS2_SETTLEMENT_JOB_NAME
        ];

        // In seconds
        $timelines = [15, 30, 45, 60, 90, 120, 150, 180];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => $this->channel,
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'Axis2 Settlement File Send Failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }
}
