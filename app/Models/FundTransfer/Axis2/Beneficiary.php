<?php

namespace RZP\Models\FundTransfer\Axis2;

use Mail;
use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Encryption\Type;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service;
use RZP\Encryption\PGPEncryption;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\OldNewIfscMapping;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\FundAccount\Type as FundAccountType;
use RZP\Models\FundTransfer\Base\Beneficiary\FileProcessor;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;

class Beneficiary extends FileProcessor
{
    const BEAM_FILE_TYPE = 'Beneficiary';

    const BENE_DEFAULT_NAME      = 'Not Available';

    protected $id;

    protected $encryptionKey  = null;

    protected $channel    = Channel::AXIS2;

    public function __construct()
    {
        parent::__construct();

        $this->encryptionKey  = Config::get('nodal.axis2.axis2_nodal_pgp_encryption_key');

        $this->encryptionKey = trim(str_replace('\n', "\n", $this->encryptionKey));
    }

    /**
     * @param $bankAccounts
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     *                         'merchants_count'
     */
    public function register(PublicCollection $bankAccounts, $accountType = FundAccountType::BANK_ACCOUNT, array $input = []): array
    {
        try
        {
            $this->increaseAllowedSystemLimits();

            $rows = $this->getData($bankAccounts);

            $this->trace->info(TraceCode::FTA_ROWS_FETCHED_FOR_FILE);

            $file = $this->generateFile($rows);

            $this->trace->info(TraceCode::FTA_FILE_CREATED_IN_S3);

            $this->sendFile($file);

            $this->trace->info(TraceCode::FTA_FILE_SEND_VIA_BEAM);

            $merchantCount = count($rows);

            $response = $this->makeResponse($file, $merchantCount, $merchantCount, true);

            $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

            $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

            $this->sendEmail($mailData);

            $this->trace->info(TraceCode::FTA_FILE_EMAIL_SENT);

            return $response;

        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::BENEFICIARY_FILE_CREATION_FAILED
            );
        }
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        // First row is heading of file based in format given
        $rows[] = Headings::getBeneficiaryFileHeadings();

        foreach ($bankAccounts as $ba)
        {
            $this->trace->info(
                TraceCode::BENEFICIARY_REGISTER_BANK_ACCOUNT,
                [
                    'bank_account_id'   => $ba->getId(),
                    'channel'           => $this->channel
                ]);

            $beneName =  $this->normalizeBeneficiaryName($ba->getBeneficiaryName());

            $bankName =  $ba->getBankNameAttribute() ?? '';

            $numLength = strlen((string)$ba->getAccountNumber());

            if ($numLength > 26)
            {
                continue;
            }

            $ifsc = $ba->getIfscCode();
            $ifscMapping = OldNewIfscMapping::$oldToNewIfscMapping;
            if (array_key_exists($ifsc, $ifscMapping) === true)
            {
                $ifsc = $ifscMapping[$ifsc];
            }

            $record[Headings::BENEFICIARY_IFSC_CODE] = $ifsc;

            $rows[] = [
                Constants::PRIME_CORP_CODE,
                Constants::CORP_CODE,
                $ba->getId(),
                $beneName,
                $ba->getAccountNumber(),
                $ifsc,
                $bankName,
                '',
                '',
            ];

            $this->trace->info(TraceCode::BANK_ACCOUNT_OLD_TO_NEW_IFSC_BEING_USED, [
                'ifsc' => $ifsc,
            ]);
        }

        return $rows;
    }

    protected function generateFile($rows): FileStore\Creator
    {
        $fileName = 'axis/poweraccess/outgoing_ben/' . $this->getFileId();;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($rows)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->encrypt(Type::PGP_ENCRYPTION,
                            [
                                PGPEncryption::PUBLIC_KEY  => $this->encryptionKey,
                                PGPEncryption::USE_ARMOR   => 1
                            ])
                        ->save();

        return $file;
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['register_count']);

        Mail::queue($beneficiaryFileMail);
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10003',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }

    protected function getFileId(): string
    {
        $timeNow   = Carbon::now(Timezone::IST);

        $date      = $timeNow->format('Y_m_d_h_i_s');

        return Constants::CORP_CODE . '_BENEREG_' . $date;
    }

    /* Normalizes beneficiary name should have length of max 70
     * Allowed characters  a-z A-Z \s \d () , : . / -
     *
     * @param $name
     * @return string
     */
    public function normalizeBeneficiaryName($name): string
    {
        if($name !== null)
        {
            $normalizedNameWithSpaces =  preg_replace('/[^a-zA-Z\/\-\:\(\).,\s\d]/', '', $name);

            $normalizedString = preg_replace('/\s+/',' ', $normalizedNameWithSpaces);

            if (strlen(trim($normalizedString)) === 0)
            {
                return self::BENE_DEFAULT_NAME;
            }

            return substr($normalizedString, 0, 70);
        }

        return self::BENE_DEFAULT_NAME;
    }


    /**
     * @param FileStore\Creator $file
     * Send file to bank through Beam
     */
    protected function sendFile(FileStore\Creator $file)
    {
        $fileInfo = [$file->getFullFileName()];

        $bucketConfig = $this->getBucketConfig(FileStore\Type::FUND_TRANSFER_H2H, $this->env);

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => BeamConstants::AXIS2_BENEFICIARY_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [15, 28, 56, 112, 225, 450, 900, 1800, 3600, 2*3600];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => $this->channel,
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'Axis2 Beneficiary File send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(900);

        RuntimeManager::setMaxExecTime(900);
    }
}
