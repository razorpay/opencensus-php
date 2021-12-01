<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Config;
use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Encryption\AESEncryption;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\FundTransfer\Base\Beneficiary\FileProcessor;

class Beneficiary extends FileProcessor
{
    const BEAM_FILE_TYPE = 'Beneficiary';

    const RZP_FILE_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    protected $id;

    protected $channel = Channel::AXIS;

    protected $secret;

    protected $iv;

    public function __construct()
    {
        parent::__construct();

        $this->id = Base\UniqueIdEntity::generateUniqueId();

        $this->secret = Config::get('nodal.axis.secret');

        $this->iv = base64_decode(Config::get('nodal.axis.iv'));
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        $headers = [
            'Beneficiary Code',
            'Beneficiary Name',
            'Beneficiary Account',
            'Bene Bank IFSC',
            'Beneficiary Bank Name',
        ];

        $rows[] = $headers;

        foreach ($bankAccounts as $ba)
        {
            $this->trace->info(
                TraceCode::BENEFICIARY_REGISTER_BANK_ACCOUNT,
                [
                    'bank_account_id'   => $ba->getId(),
                    'channel'           => $this->channel
                ]);

            $beneName =  $this->normalizeBeneficiaryName($ba->getBeneficiaryName());

            $ifsc = strtoupper($ba->getIfscCode());

            //Axis bank EPH system does not support beneficiary registration
            //with IFSC code having character after UTIB.
            //Examples : UTIB0CCH274
            if ((substr($ifsc, 0, 4) === 'UTIB') and
               (preg_match('/^\d{7}+$/', substr($ifsc, 4)) === 0))
            {
                $this->trace->info(
                    TraceCode::BENEFICIARY_UNSUPPORTED_IFSC_CODE,
                    [
                        'bank_account_id'   => $ba->getId(),
                        'channel'           => $this->channel,
                        'ifsc_code'         => $ba->getIfscCode(),
                    ]);

                continue;
            }

            $rows[] = [
                $ba->getId(),
                $beneName,
                $ba->getAccountNumber(),
                $ifsc,
                '', // Axis needs us to send this value as blank
            ];
        }

        return $rows;
    }

    protected function generateFile($data): FileStore\Creator
    {
        $fileName = 'axis/beneficiary/' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->mime(self::RZP_FILE_MIME_TYPE)
                        ->content($data)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->encrypt(Type::AES_ENCRYPTION, [
                            AESEncryption::MODE   => AES::MODE_CBC,
                            AESEncryption::IV     => $this->iv,
                            AESEncryption::SECRET => $this->secret,])
                        ->encode()
                        ->save();

        return $file;
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

    /**
     * collects the bene data, creates file and gives back the summary
     *
     * @param PublicCollection $bankAccounts
     * @return array
     */
    public function registerBeneficiary(PublicCollection $bankAccounts): array
    {
        $file          = new FileStore\Creator;

        $totalCount    = $bankAccounts->count();

        $fileCreated   = false;

        $data          = $this->getData($bankAccounts);

        $registerCount = count($data) - 1;

        if ($registerCount !== 0)
        {
            $file        = $this->generateFile($data);

            $fileCreated = true;

            // Pushing to Beam after sending the email
            // such that current beneficiary processing
            // doesn't get affected by Beam errors.
            $this->sendFile($file);
        }

        $response = $this->makeResponse($file, $totalCount, $registerCount, $fileCreated);

        return $response;
    }


    /**
     * Place holder method for verifyBeneficiary since parent abstract class has it.
     * @param PublicCollection $bankAccounts
     * @return array
     * @throws Exception\LogicException
     */
    public function verifyBeneficiary(PublicCollection $bankAccounts): array
    {
        throw new Exception\LogicException('Beneficiary verification not supported for channel '.$this->channel);

        return [];
    }

    /* Normalizes beneficiary name should have length of max 50
     * Allowed characters  a-z A-Z @ # $ & ( ) - , + { } . [ ] " ; : ? / * \ ` ~
     *
     * @param $name
     *
     * @return string
     */
    protected function normalizeBeneficiaryName($name): string
    {
        if($name !== null)
        {
            $normalizedNameWithSpaces =  preg_replace('/[^a-zA-Z\s@#\$&\(\)\-,\"\+~`\{\}\.~;:\?\*\[\]\/\\\]/', '', $name);

            $normalizedString = preg_replace('/\s+/',' ', $normalizedNameWithSpaces);

            return substr($normalizedString, 0, 50);
        }

        return $name;
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
            Service::BEAM_PUSH_JOBNAME => BeamConstants::AXIS_BENEFICIARY_JOB_NAME,
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
