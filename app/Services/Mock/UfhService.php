<?php

namespace RZP\Services\Mock;

use RZP\Models\Base\Entity;
use RZP\Services\UfhService as BaseUfhClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService extends BaseUfhClient
{
    const MOCK_FILE_ID      = 'rzp_file_mock_id_1000000';

    const MOCK_BASE_URL     = 'https://mock.rzp.io/storage/s3/';

    /**
     * @param UploadedFile $file
     * @param string $storageFileName
     * @param string $type
     * @param Entity $entity
     * @return array
     */
    public function uploadFileAndGetUrl(
                                        UploadedFile $file,
                                        string $storageFileName,
                                        string $type,
                                        Entity $entity): array
    {
        return [
            self::FILE_ID    => self::MOCK_FILE_ID,
            self::SIGNED_URL => self::MOCK_BASE_URL . $storageFileName,
        ];
    }
}