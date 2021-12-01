<?php

namespace RZP\Models\FundTransfer\Icici;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\FundAccount\Type;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Models\FundTransfer\Base\Beneficiary\FileProcessor;

class Beneficiary extends FileProcessor
{
    const BEAM_FILE_TYPE = 'Beneficiary';

    const RZP_FILE_MIME_TYPE  = 'text/plain';

    protected $id;

    protected $channel = Channel::ICICI;

    public function __construct()
    {
        parent::__construct();

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    /**
     * @param PublicCollection $bankAccounts
     * @param array $input
     * @return array
     */
    public function register(PublicCollection $bankAccounts, $accountType = Type::BANK_ACCOUNT, array $input = []): array
    {
        $file          = new FileStore\Creator;

        $fileCreated   = false;

        $totalCount    = $bankAccounts->count();

        $rows          = $this->getData($bankAccounts);

        $registerCount = count($rows);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_DATA_FETCHED);

        if ($registerCount !== 0)
        {
            $txt         = $this->getTxt($rows);

            $file        = $this->generateFile($txt);

            $fileCreated = true;

            $this->trace->info(TraceCode::BENEFICIARY_REGISTER_FILE_CREATED);
        }

        $response = $this->makeResponse($file, $totalCount, $registerCount, $fileCreated);

        if ($registerCount === 0)
        {
            return $response;
        }

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_EMAIL_SENT, ['mail_data' => $mailData]);

        //
        // Pushing to Beam after sending the email
        // such that current beneficiary processing
        // doesn't get affected by Beam errors.
        //
        $this->sendFile($file);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_FILE_SEND_VIA_BEAM);

        return $response;
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        foreach ($bankAccounts as $ba)
        {
            $this->trace->info(
                TraceCode::BENEFICIARY_REGISTER_BANK_ACCOUNT,
                [
                    'bank_account_id'   => $ba->getId(),
                    'channel'           => $this->channel
                ]);

            $address = 'Beneficiary address not available';

            $merchantDetails = $ba->source->merchantDetail;

            if ($merchantDetails !== null)
            {
                $address = $merchantDetails->getBusinessRegisteredAddress();
            }

            // Removes line break from the string
            $address = $this->normalizeString($address, 30, '');

            $row = [
                'A',
                $ba->getId(),
                $ba->getBeneficiaryName(),
                $ba->getAccountNumber(),
                'vendor',
                $address,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getTxt(array $rows): string
    {
        $txt = '';

        $totalElements = count($rows);

        foreach ($rows as $index => $row)
        {
            $txt .= implode('|', $row);

            if ($index < $totalElements - 1)
            {
                //
                // Double quote is required to suggest new line
                // Single quote will NOT work
                //
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    protected function generateFile($txt): FileStore\Creator
    {
        $fileName = 'icici/outgoing/NRPSS_NRPSSBENEUPLD_' . $this->id;

        if ($this->env === 'beta')
        {
            $fileName = 'icici/outgoing/TEST_BENEUPLD_' . $this->id;
        }

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->mime(self::RZP_FILE_MIME_TYPE)
                        ->content($txt)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->id($this->id)
                        ->metadata($metadata)
                        ->save();

        return $file;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['register_count']);

        Mail::queue($beneficiaryFileMail);
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
            Service::BEAM_PUSH_JOBNAME => BeamConstants::ICICI_BENEFICIARY_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [15, 28, 56, 112, 225, 450, 900, 1800, 3600, 2*3600];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => $this->channel,
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'File send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }
}
