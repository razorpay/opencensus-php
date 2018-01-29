<?php

namespace RZP\Services\Mock;

use RZP\Trace\TraceCode;
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
        $filePath = $file->getPath() . '/' . $file->getFileName();

        $requestData = [
            'file'          => fopen($filePath, 'r'),
            'name'          => $storageFileName,
            'extension'     => $file->getExtension(),
            'type'          => $type,
            'entity_id'     => $entity->getPublicId(),
            'entity_type'   => $entity->getEntityName(),
            'store'         => $this->getStoreForEnv(),
        ];

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, ['file']));

        return [
            self::FILE_ID    => self::MOCK_FILE_ID,
            self::SIGNED_URL => self::MOCK_BASE_URL . $storageFileName,
        ];
    }
}