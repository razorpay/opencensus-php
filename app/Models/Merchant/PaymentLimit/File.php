<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Models\FileStore;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Merchant\Fraud\BulkNotification\File as BaseFile;

class File extends BaseFile
{
    use FileHandlerTrait;

    public function saveLocalFile(UploadedFile $file, PublicEntity $entity)
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::XLSX)
            ->localFile($file)
            ->name($file->getClientOriginalName())
            ->entity($entity)
            ->store(FileStore\Store::S3)
            ->type(FileStore\Type::PAYMENT_LIMIT)
            ->save();
    }

    public function saveFileWithFormattedHeader(array $fileData, string $fileName, Entity $entity): FileStore\Creator
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::CSV)
            ->content($fileData)
            ->name($fileName)
            ->headers(false)
            ->entity($entity)
            ->store(FileStore\Store::S3)
            ->type(FileStore\Type::PAYMENT_LIMIT)
            ->deleteLocalFile()
            ->save();

        return $creator;
    }
}
