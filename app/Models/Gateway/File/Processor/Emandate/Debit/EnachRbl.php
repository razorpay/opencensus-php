<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Base\RuntimeManager;
use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base as ModelBase;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Gateway\EMandate\Base as EMandateMail;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;
use RZP\Trace\TraceCode;

class EnachRbl extends Base
{
    const ACQUIRER  = Payment\Gateway::ACQUIRER_RATN;

    const GATEWAY   = Payment\Gateway::ENACH_RBL;

    const EXTENSION = FileStore\Format::XLSX;

    const FILE_TYPE = FileStore\Type::RBL_ENACH_DEBIT;

    const FILE_NAME = 'rbl-enach/outgoing/TXN_INP/ACH-DR-RATN-RATNA0001-{$date}-000001-INP';

    const STEP      = 'debit';

    const FILE_METADATA  = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];

    protected $fileStore;

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;
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
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt([]);

            $creator = new FileStore\Creator;

            $creator->extension(self::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(self::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(self::FILE_METADATA)
                    ->save();

            $file = $creator->getFileInstance();

            $fileStoreIds[] = $file->getId();

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_DEBIT_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "GEN_RBL");

        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_GENERATION_ERROR);

            $this->trace->traceException($e);

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
     * @throws GatewayFileException
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
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::RBL_ENACH_DEBIT_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [60, 300, 900, 1800, 3600];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::RBL_ENACH_DEBIT,
            'subject'   => 'Enach RBL Debit File Beam Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $this->sendBeamRequest($data, $timelines, $mailInfo, true);

        $mailData = $this->formatDataForMail($files);

        $type = static::GATEWAY . '_' . static::STEP;

        $mailable = new EMandateMail($mailData, $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $debitDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('d/m/Y');

            $row = [
                Headings::UTILITYCODE             => $token->terminal->getGatewayMerchantId(),
                Headings::TRANSACTIONTYPE         => 'ACH DR',
                Headings::SETTLEMENTDATE          => $debitDate,
                Headings::BENEFICIARYACHOLDERNAME => $token->getBeneficiaryName(),
                Headings::AMOUNT                  => $this->getFormattedAmount($token['payment_amount']),
                Headings::DESTINATIONBANKCODE     => $token->getIfsc(),
                Headings::BENEFICIARYACNO         => $token->getAccountNumber(),
                Headings::TRANSACTIONREFERENCE    => $paymentId,
                Headings::UMRN                    => $token->getGatewayToken(),
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date]);

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
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
    
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('8192M'); // 8GB
        
        RuntimeManager::setTimeLimit(7200);
        
        RuntimeManager::setMaxExecTime(7200);
    }
}
