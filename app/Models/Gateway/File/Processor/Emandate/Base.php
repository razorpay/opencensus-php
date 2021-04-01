<?php

namespace RZP\Models\Gateway\File\Processor\Emandate;

use Mail;
use Storage;
use ZipArchive;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Exception\RuntimeException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Gateway\EMandate\Base as EMandateMail;

abstract class Base extends Processor\Base
{
    const FILE_METADATA = [];

    const S3_PATH = '';

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
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id' => $this->gatewayFile->getId()
                ],
                $e);
        }
    }

    protected function getFileToWriteNameWithoutExt(array $data)
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

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function generateZipFile($zipFileTarget)
    {
        $files = Storage::files($zipFileTarget);

        $zipFileLocalName = basename($zipFileTarget);

        $zipFileLocalPath = $this->getLocalSaveDir() . DIRECTORY_SEPARATOR . $zipFileLocalName . '.zip';

        $zipFileS3Name = self::S3_PATH . basename($zipFileTarget);

        $zip = new ZipArchive();

        if ($zip->open($zipFileLocalPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(
                'Could not create Papernach zip file',
                [
                    'filename' => $zipFileLocalPath
                ]);
        }

        $basePath = storage_path('app') . '/';

        foreach ($files as $file)
        {
            $filePath = $basePath . $file;

            $zip->addFile($filePath, basename($file));
        }

        $zip->close();

        $zipCreator = new FileStore\Creator;

        $zipCreator->extension(static::EXTENSION)
                   ->localFilePath($zipFileLocalPath)
                   ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::EXTENSION][0])
                   ->name($zipFileS3Name)
                   ->store(FileStore\Store::S3)
                   ->type(static::FILE_TYPE)
                   ->entity($this->gatewayFile)
                   ->save();

        $file = $zipCreator->getFileInstance();

        $this->fileStore[] = $file->getId();

        $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

        unlink($zipFileLocalPath);
    }
}
