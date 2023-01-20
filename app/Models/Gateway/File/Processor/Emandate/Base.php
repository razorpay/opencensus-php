<?php

namespace RZP\Models\Gateway\File\Processor\Emandate;

use Mail;
use Storage;
use ZipArchive;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Metric;
use RZP\Exception\RuntimeException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor;
use RZP\Exception\GatewayFileException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Mail\Gateway\EMandate\Base as EMandateMail;

abstract class Base extends Processor\Base
{
    const FILE_METADATA = [];

    const EMANDATE_QUERY_OPTIMIZATION = 'emandate_query_optimization';

    public function checkIfValidDataAvailable(PublicCollection $tokens)
    {
        if ($tokens->count() === 0)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
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
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt([]);

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

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::EMANDATE_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "OTHER_BANKS");
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

    public function sendFile($data)
    {
        try
        {
            $recipients = $this->gatewayFile->getRecipients();

            $mailData = $this->formatDataForMail($data);

            $type = static::GATEWAY . '_' . static::STEP;
            $mailable = new EMandateMail($mailData, $type, $recipients);

            Mail::queue($mailable);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_SENT_ERROR);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ],
                $e);
        }
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $time = Carbon::now(Timezone::IST)->format('dmYHis');

        if ($this->isTestMode() === true)
        {
            return static::FILE_NAME . '_' . $time . '_' . $this->mode;
        }

        return static::FILE_NAME . '_' . $time;
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function formatDataForMail($data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'     => $file->getLocation(),
            'signed_url'    => $signedUrl,
        ];

        return $mailData;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getBucketConfig($fileType)
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($fileType, $this->env);

        return $config[$bucketType];
    }
}
