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

    public function __construct($merchantId = null)
    {
        $app = App::getFacadeRoot();

        $ufhServiceMock = $app['config']->get('applications.ufh.mock');

        if ($ufhServiceMock === true)
        {
            $this->ufhService = new \RZP\Services\Mock\UfhService($app);
        }
        else
        {
            $this->ufhService = new UfhService($app, $app['basicauth']->getMerchantId());
        }
    }

    public function uploadFile(array $input): array
    {
        $results = [];

        $file     = $input[Constants::FILE];
        $type     = $input[Constants::TYPE];
        $fileName = $input[Constants::FILE_NAME];

        $fileMetaData = [
            Constants::CONTENT_DISPOSITION => Constants::CONTENT_DISPOSITION_INLINE
        ];

        $fileMetaData = $this->ufhService->uploadFileAndGetUrl($file,
                                                               $fileName,
                                                               $type,
                                                               $input[Constants::ENTITY],
                                                               $fileMetaData);

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
