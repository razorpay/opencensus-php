<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Illuminate\Http\UploadedFile;

use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Entity;
use RZP\Models\FileStore;

class File
{
    use FileHandlerTrait;

    public function getFilepathWithExtension(string $filepath, string $extension): string
    {
        if (ends_with($filepath, $extension) === false)
        {
            $filepathWExt = $filepath . '.' . $extension;

            rename($filepath, $filepathWExt);

            $filepath = $filepathWExt;
        }

        return $filepath;
    }

    public function getFileData(UploadedFile $file): array
    {
        $filepath = $file->getRealPath();

        $extension = $file->getClientOriginalExtension();

        $filepath = $this->getFilepathWithExtension($filepath, $extension);

        $data = $this->parseExcelSheets($filepath, 0);

        // Delete Local File
        unlink($filepath); // nosemgrep : php.lang.security.unlink-use.unlink-use

        return $data;
    }

    public function saveLocalFile(UploadedFile $file, PublicEntity $entity)
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::XLSX)
                ->localFile($file)
                ->name($file->getClientOriginalName())
                ->entity($entity)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::BULK_FRAUD_NOTIFICATION)
                ->save();
    }

    public function saveFile(array $fileData, string $fileName, Entity $entity): FileStore\Creator
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::XLSX)
                ->content($fileData)
                ->name($fileName)
                ->entity($entity)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::BULK_FRAUD_NOTIFICATION)
                ->deleteLocalFile()
                ->save();

        return $creator;
    }
}
