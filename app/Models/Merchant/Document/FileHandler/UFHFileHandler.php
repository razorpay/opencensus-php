<?php

namespace RZP\Models\Merchant\Document\FileHandler;

use App;

use RZP\Models\FileStore;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\Document\Constants;

class UFHFileHandler implements FileHandlerInterface
{
    protected $ufhService;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->ufhService = $app['ufh.service'];
    }

    public function uploadFile(array $input): array
    {
        $results = [];

        $file     = $input[Constants::FILE];
        $type     = $input[Constants::TYPE];
        $fileName = $input[Constants::FILE_NAME];

        $fileMetaData = $this->ufhService->uploadFileAndGetUrl($file,
                                                               $fileName,
                                                               $type,
                                                               $input[Constants::ENTITY]);

        $results[Constants::FILE_ID] = FileStore\Entity::verifyIdAndSilentlyStripSign($fileMetaData[UfhService::FILE_ID]);
        $results[Constants::SOURCE]  = $this->getSource();

        return $results;
    }

    public function getSignedUrl(string $fileStoreId, string $merchantId): string
    {
        $ufhPublicId = FileStore\Entity::getIdPrefix() . $fileStoreId;

        $signedUrl = $this->ufhService->getSignedUrl($ufhPublicId, [], $merchantId);

        return $signedUrl['signed_url'] ?? null;
    }

    public function getSource(): string
    {
        return Source::UFH;
    }
}
