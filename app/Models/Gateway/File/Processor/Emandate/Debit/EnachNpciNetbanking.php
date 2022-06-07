<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

Use Config;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Base as ModelBase;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Gateway\Enach\Npci\Netbanking\DebitFileHeading as Headings;

class EnachNpciNetbanking extends Base
{
    const ACQUIRER  = Payment\Gateway::ACQUIRER_YESB;

    const GATEWAY   = Payment\Gateway::ENACH_NPCI_NETBANKING;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::ENACH_NPCI_NB_DEBIT;

    const BASE_STORAGE_DIRECTORY = 'Npci/Enach/Netbanking/';

    const FILE_NAME = 'yesbank/nach/input_file/NACH_DR_{$date}_{$utilityCode}_RAZORPAY_001';

    protected $fileStore;

    const STEP = 'debit';

    const FILE_METADATA  = [
        'gid'   => '10000',
        'uid'   => '10004',
        'mode'  => '33188'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $terminal = $token->terminal;

            $paymentId = $token['payment_id'];

            $debitDate = Carbon::today(Timezone::IST)->format('dmY');

            $rows[$terminal->getGatewayMerchantId2()][] = [
                Headings::PAYMENT_ID              => $paymentId,
                Headings::UMRN                    => $token->getGatewayToken(),
                Headings::AMOUNT                  => $this->getFormattedAmount($token['payment_amount']),
                Headings::SETTLEMENT_DATE         => $debitDate,
                Headings::UTILITY_CODE            => $token->terminal->getGatewayMerchantId2(),
            ];

        }

        return $rows;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date, '{$utilityCode}' => $data['utilityCode']]);

        return self::BASE_STORAGE_DIRECTORY . $fileName;
    }

    protected function getNewGatewayPaymentEntity(): Enach\Base\Entity
    {
        return new Enach\Base\Entity;
    }

    protected function getGatewayAttributes(ModelBase\PublicEntity $token): array
    {
        return [
            Enach\Base\Entity::ACQUIRER => self::ACQUIRER,
            Enach\Base\Entity::UMRN     => $token['gateway_token'],
        ];
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

        $bucketConfig = $this->getBucketConfig(FileStore\Type::ENACH_NPCI_NB_DEBIT);

        $data =  [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::YESBANK_ENACH_NB_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'emandate',
            'filetype'  => FileStore\Type::ENACH_NPCI_NB_DEBIT,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::NBPLUS_TECH]
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
                    'target'        => 'enach_npci_netbanking',
                ]
            );
        }
    }

    /**
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        Config::set('excel.exports.csv.enclosure', '');

        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                // since file data is grouped based on utility code, it will be part of the file name
                $fileName = $this->getFileToWriteNameWithoutExt(['utilityCode' => $key]);

                $creator = new FileStore\Creator;

                $creator->extension(static::EXTENSION)
                        ->content($fileData)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(static::FILE_TYPE)
                        ->entity($this->gatewayFile)
                        ->metadata(static::FILE_METADATA)
                        ->save();

                $file = $creator->getFileInstance();

                $fileStoreIds[] = $file->getId();

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());
            }

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

        Config::set('excel.exports.csv.enclosure', '"');
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
            $tokens = $this->repo->token->fetchPendingEMandateDebitWithGatewayAcquirer(
                    static::GATEWAY,
                    $begin,
                    $end,
                    Payment\Gateway::ACQUIRER_YESB);
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
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
                'count'           => count($paymentIds),
            ]);

        return $tokens;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('8192'); // 8gb
    }
}
