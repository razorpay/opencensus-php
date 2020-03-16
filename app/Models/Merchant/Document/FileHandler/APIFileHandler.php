<?php

namespace RZP\Models\Merchant\Document\FileHandler;

use RZP\Models\FileStore;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\Document\Constants;

class APIFileHandler implements FileHandlerInterface
{

    protected $core;

    protected $creator;

    public function __construct()
    {
        $this->core    = new FileStore\Core;
        $this->creator = new FileStore\Creator;
    }

    public function uploadFile(array $input): array
    {
        $result        = [];
        $inputFile     = $input[Constants::FILE];
        $inputFileType = $input[Constants::TYPE];
        $merchant      = $input[Constants::MERCHANT];

        $file = $this->creator->extension($inputFile->extension())
                              ->localFile($inputFile)
                              ->name($input[Constants::FILE_NAME])
                              ->store($input[FileStore\Entity::STORE] ?? FileStore\Store::S3)
                              ->type($inputFileType)
                              ->merchant($merchant)
                              ->save()
                              ->get();

        $result[Constants::FILE_ID] = FileStore\Entity::verifyIdAndSilentlyStripSign($file['id']);
        $result[Constants::SOURCE]  = $this->getSource();

        return $result;
    }

    public function getSignedUrl(string $fileStoreId, string $merchantId): string
    {
        return $this->core->getSignedUrl($fileStoreId, $merchantId);
    }

    public function getSource(): string
    {
        return Source::API;
    }
}
