<?php

namespace RZP\Models\Gateway\File\Processor\Nach;

use Mail;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor;
use RZP\Exception\GatewayFileException;
use RZP\Models\FileStore\Storage\Base\Bucket;

abstract class Base extends Processor\Base
{
    const FILE_METADATA = [];

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

    protected function getFileToWriteNameWithoutExt(array $data)
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

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

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function formatDataForMail($files): array
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

    protected function getLastWorkingDay($timestamp)
    {
        $date = (new Carbon())->timestamp($timestamp);

        while (Holidays::isWorkingDay($date) === false)
        {
            $date = $date->subDay();
        }

        return $date->timestamp;
    }

    protected function getBucketConfig($fileType)
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($fileType, $this->env);

        return $config[$bucketType];
    }
}
