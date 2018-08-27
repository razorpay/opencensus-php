<?php

namespace RZP\Services\Mock;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Entity;
use RZP\Services\UfhService as BaseUfhClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService extends BaseUfhClient
{
    const MOCK_FILE_ID      = 'rzp_file_mock_id_1000000';

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
        $ext = $file->getClientOriginalExtension();

        $movedFile = $file->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

        $requestData = [
            'file'          => fopen($movedFile->getPathname(), 'r'),
            'name'          => $storageFileName,
            'type'          => $type,
            'entity_id'     => $entity->getPublicId(),
            'entity_type'   => $entity->getEntityName(),
            'store'         => $this->getStoreForEnv(),
        ];

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, ['file']));

        return [
            self::FILE_ID           => self::MOCK_FILE_ID . "_$type",
            self::RELATIVE_LOCATION => $storageFileName,
        ];
    }

    public function fetchFiles(array $queryParams): array
    {
        return [
            'entity'  => 'collection',
            'count'   => 2,
            'items'   => [
                [
                    'id'            => 'file_1234',
                    'type'          => 'explanation_letter',
                    'entity_type'   => $queryParams['entity_type'],
                    'entity_id'     => $queryParams['entity_id'],
                    'name'          => 'myfile1.png',
                    'location'      => 'dispute/10000000000000/'. $queryParams['entity_id'] .'/myfile1.png',
                    'bucket'        => 'test_bucket',
                    'mime'          => 'text/csv',
                    'extension'     => 'csv',
                    'merchant_id'   => '10000000000000',
                    'store'         => 's3',
                ],
                [
                    'id'            => 'file_12345',
                    'type'          => 'delivery_proof',
                    'entity_type'   => $queryParams['entity_type'],
                    'entity_id'     => $queryParams['entity_id'],
                    'name'          => 'myfile2.pdf',
                    'location'      => 'dispute/10000000000000/'. $queryParams['entity_id'] .'/myfile2.pdf',
                    'bucket'        => 'test_bucket',
                    'mime'          => 'text/csv',
                    'extension'     => 'csv',
                    'merchant_id'   => '10000000000000',
                    'store'         => 's3',
                ],
            ],
        ];
    }

    public function deletefile(string $fileId) {}
}
