<?php

namespace RZP\Models\Merchant\Document\FileHandler;

use App;

use RZP\Models\FileStore;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\Document\Constants;
use RZP\Services\Mock\UfhService as MockUfhService;

class UFHFileHandler implements FileHandlerInterface
{
    protected $ufhService;

    public function __construct($merchantId = null)
    {
        $app = App::getFacadeRoot();

        $this->setUfhService($app);
    }

    protected function setUfhService($app)
    {
        $ufhServiceMock = $app['config']->get('applications.ufh.mock');

        if($ufhServiceMock === true)
        {
            $this->ufhService = new MockUfhService($app);
        }
        else
        {
            $this->ufhService = new UfhService($app, $app['basicauth']->getMerchantId(), "pg_onboarding");
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

        $results[Constants::FILE_ID]            = FileStore\Entity::verifyIdAndSilentlyStripSign($fileMetaData[UfhService::FILE_ID]);
        $results[Constants::SOURCE]             = $this->getSource();
        $results[Constants::FILE_NAME]          = $input[Constants::FILE_NAME];
        $results[Constants::ORIGINAL_FILE_NAME] = $input[Constants::ORIGINAL_FILE_NAME];

        return $results;
    }

    public function getSignedUrl(string $fileStoreId, string $merchantId): string
    {
        $ufhPublicId = FileStore\Entity::getIdPrefix() . $fileStoreId;

        $signedUrl = $this->ufhService->getSignedUrl($ufhPublicId, [], $merchantId);

        return $signedUrl['signed_url'] ?? '';
    }

    public function getSource(): string
    {
        return Source::UFH;
    }
}
