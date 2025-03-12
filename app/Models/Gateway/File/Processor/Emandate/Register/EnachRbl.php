<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Register;

use Mail;
use RZP\Models\Gateway\File\Metric;
use ZipArchive;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\FileStore\Utility;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Gateway\EMandate\Base as EMandateMail;
use RZP\Models\Gateway\File\Processor\Emandate\Base;

class EnachRbl extends Base
{
    const STEP                  = 'register';
    const GATEWAY               = Payment\Gateway::ENACH_RBL;
    const FILE_NAME             = 'rbl-enach/outgoing/MNDT_INP/MMS-CREATE-RATN-RATNA0001-{$date}-ESIGN{$sequence}-INP';
    const INDIVIDUAL_FILE_NAME  = 'MMS-CREATE-RATN-RATNA0001-{$date}-ESIGN{$sequence}-INP';
    const EXTENSION             = FileStore\Format::ZIP;
    const INDIVIDUAL_EXTENSION  = FileStore\Format::XML;
    const FILE_TYPE             = FileStore\Type::RBL_ENACH_REGISTER;
    const FILE_METADATA         = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];

    const NUM_SECS_IN_ONE_DAY = 86400;
    /**
     * @var array
     */
    protected $fileStore;

    protected $zipFileSize = 900;

    public function fetchEntities(): PublicCollection
    {
        //
        // We add one day to this because in case of enach, we fetch the
        // entities from enach entity. In enach entity, we store the date
        // when we are supposed to pick that entity up in the cron.
        // In this case, we don't care about the time, but only the date.
        // So, on day T, we should be picking up all enach entities which
        // have registration_date set to day T.
        // Hence, we are adding one day to gateway file's begin and end because
        // gateway file's begin and end are always automatically set to the
        // previous day's begin and end. So, on day T, gateway file's
        // begin and end will be set to that of day T-1.
        //
        $begin = $this->gatewayFile->getBegin() + self::NUM_SECS_IN_ONE_DAY;
        $end = $this->gatewayFile->getEnd() + self::NUM_SECS_IN_ONE_DAY;

        try
        {
            $payments = $this->repo->payment->fetchPendingEmandateRegistrationForEnachOptimised($begin, $end);
        }
        catch (ServerErrorException $e)
        {
            $this->generateMetricForEmandate(Metric::EMANDATE_DB_ERROR);
            
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->trace->info(TraceCode::EMANDATE_REGISTRATION_RBL_QUERY, [
            "ENACH_RBL_QUERY_LOG" => "QUERY_RETURNED",
        ]);

        $paymentIds = $payments->pluck(Payment\Entity::ID)->toArray();
        
        $this->generateMetricForEmandate(Metric::EMANDATE_DB_QUERY_COMPLETE);
        
        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
                'target' => $this->gatewayFile->getTarget(),
                'type'   => $this->gatewayFile->getType()
            ]);

        return $payments;
    }

    public function generateData(PublicCollection $payments)
    {
        return $payments;
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
            $fileStoreIds = [];

            $fileData = $this->formatDataForFile($data);

            $count = count($fileData);

            $totalZipFiles = ((int) floor($count / $this->zipFileSize));

            $zipFileNum = 0;

            while($zipFileNum <= $totalZipFiles)
            {
                $fileName = $this->getZipFileToWriteName($zipFileNum + 1, false);

                $zipFilePath = $this->getLocalSaveDir() . DIRECTORY_SEPARATOR . $fileName . '.zip';

                $fileName = $this->getZipFileToWriteName($zipFileNum + 1);

                [$startInd, $endInd] = $this->getSubarrayIndexes($zipFileNum, $totalZipFiles, $count);

                $this->createZipFileWithData($fileData, $zipFilePath, $startInd, $endInd);

                $creator = new FileStore\Creator;

                $file = $creator->extension(static::EXTENSION)
                    ->localFilePath($zipFilePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::EXTENSION][0])
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(static::FILE_METADATA)
                    ->save()
                    ->getFileInstance();

                $fileStoreIds[] = $file->getId();

                $this->fileStore = $fileStoreIds;

                $zipFileNum++;
            }

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
            
            $this->generateMetricForEmandate(Metric::EMANDATE_FILE_GENERATED);
            
            $this->trace->info(
                TraceCode::EMANDATE_REGISTER_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            unlink($zipFilePath); // nosemgrep : php.lang.security.unlink-use.unlink-use
        }
        catch (\Throwable $e)
        {
            $this->generateMetricForEmandate(Metric::EMANDATE_FILE_GENERATION_ERROR);
            
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }
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

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::RBL_ENACH_REGISTER_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [60, 300, 900, 1800, 3600];

        $mailInfo = [
            'fileInfo' => $fileInfo,
            'channel' => 'nach',
            'filetype' => FileStore\Type::RBL_ENACH_REGISTER,
            'subject' => 'Enach RBL Register File Beam Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $this->sendBeamRequest($data, $timelines, $mailInfo, true);

        $mailData = $this->formatDataForMail($files);

        $type = static::GATEWAY . '_' . static::STEP;

        $mailable = new EMandateMail($mailData, $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
        
        $this->generateMetricForEmandate(Metric::EMANDATE_FILE_SENT);
        
    }

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $payment)
        {
            $signedXml = $payment->enach->getSignedXml();

            if (empty($signedXml) === true)
            {
                throw new LogicException(
                    'Found empty signed xml for enach entity',
                    ErrorCode::SERVER_ERROR_SIGNED_XML_EMPTY,
                    [
                        'payment_id'    => $payment->getId()
                    ]);
            }

            $rows[] = $signedXml;
        }

        return $rows;
    }

    protected function createZipFileWithData(array $signedXmlData, string $zipFilePath, $startXmlInd, $endXmlInd)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true)
        {
            throw new RuntimeException(
                'Could not create enach zip file',
                [
                    'filename' => $zipFilePath
                ]);
        }

        while($startXmlInd <= $endXmlInd)
        {
            $fileName = $this->getIndividualFileToWriteNameWithExt($startXmlInd + 1);

            $zip->addFromString($fileName, $signedXmlData[$startXmlInd]);

            $startXmlInd++;
        }

        $zip->close();
    }

    protected function getZipFileToWriteName($index, $withFullFilePath = true)
    {
        $begin = $this->gatewayFile->getBegin() + self::NUM_SECS_IN_ONE_DAY;

        $date = Carbon::createFromTimestamp($begin, Timezone::IST)->format('dmY');

        $sequence = str_pad($index, 6, '0', STR_PAD_LEFT);

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date, '{$sequence}' => $sequence]);

        if ($withFullFilePath === false)
        {
            $fileName = basename($fileName);
        }

        if ($this->isTestMode() === true)
        {
            $fileName .= '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getIndividualFileToWriteNameWithExt($index)
    {
        $begin = $this->gatewayFile->getBegin() + self::NUM_SECS_IN_ONE_DAY;

        $date = Carbon::createFromTimestamp($begin, Timezone::IST)->format('dmY');

        $sequence = str_pad($index, 6, '0', STR_PAD_LEFT);

        $fileName = strtr(static::INDIVIDUAL_FILE_NAME, ['{$date}' => $date, '{$sequence}' => $sequence]);

        if ($this->isTestMode() === true)
        {
            $fileName = $fileName . '_' . $this->mode;
        }

        $fileName .= '.' . static::INDIVIDUAL_EXTENSION;

        return $fileName;
    }

    protected function getStorageDir()
    {
        return storage_path(FileStore\Store::STORAGE_DIRECTORY);
    }

    protected function getLocalSaveDir(): string
    {
        $dirPath = storage_path('files/emandate');

        if (file_exists($dirPath) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dirPath, 0777, true]);
        }

        return $dirPath;
    }

    protected function formatDataForMail($files)
    {
        $mailData = [
            'files' => [],
        ];

        foreach ($files as $file)
        {
            $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

            $mailData['files'][] = [
                'signed_url' => $signedUrl,
                'file_name'  => $file->getLocation(),
            ];
        }

        return $mailData;
    }

    protected function getSubarrayIndexes($zipFileNum, $totalZipFiles, $totalRecords): array
    {
        $startInd = ($zipFileNum * $this->zipFileSize);
        if($zipFileNum === $totalZipFiles){
            $remainder = ($totalRecords % $this->zipFileSize);
            $endInd = $startInd + $remainder - 1;
        }
        else
        {
            $endInd = (($zipFileNum + 1) * $this->zipFileSize) - 1;
        }
        return [$startInd, $endInd];
    }
}
