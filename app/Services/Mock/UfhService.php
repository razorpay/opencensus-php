<?php

namespace RZP\Services\Mock;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Entity;
use RZP\Services\UfhService as BaseUfhClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService extends BaseUfhClient
{
    const MOCK_FILE_ID      = 'file_1cXSLlUU8V9sXl';

    /**
     * {@inheritDoc}
     */
    public function uploadFileAndGetUrl(UploadedFile $file,
                                        string $storageFileName,
                                        string $type, $entity,
                                        array $metadata = []): array
    {

        $ext = $file->getClientOriginalExtension();

        $movedFile = $file;

        // this mock service is being used in test cases . We should not change the file location of input file
        // But in some test cases we are doing that
        //
        if ($ext !== 'png')
        {
            $movedFile = $file->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);
        }

        $requestData = $this->getRequestData($file, $movedFile, $storageFileName, $type, $entity, $metadata);

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            array_except($requestData, ['file']));

        return [
            self::FILE_ID           => self::MOCK_FILE_ID,
            self::RELATIVE_LOCATION => $storageFileName,
            self::LOCAL_FILE        => $movedFile,
        ];
    }

    public function uploadFileAndGetResponse(UploadedFile $file,
                                             string $storageFileName,
                                             string $type,
                                             $entity,
                                             array $metadata = []): array
    {

        $storageFileName = strtolower($storageFileName);

        $requestData = $this->getRequestData($file, $file, $storageFileName, $type, $entity, $metadata);

        $this->trace->info(
            TraceCode::UFH_FILE_UPLOAD,
            array_except($requestData, [self::FILE]));

        return [
            'id'         => self::MOCK_FILE_ID,
            'type'       => $type,
            'name'       => $storageFileName,
            'created_at' => time(),
            'mime'       => 'image/png',
            'location'   => $storageFileName,
            'size'       => 12345
        ];
    }

    public function fetchFiles(array $queryParams, $merchantId = null): array
    {
        $entityId = $queryParams['entity_id'] ?? 'id1';
        return [
            'entity'  => 'collection',
            'count'   => 2,
            'items'   => [
                [
                    'id'            => 'file_1cXSLlUU8V9sXl',
                    'type'          => 'explanation_letter',
                    'entity_type'   => $queryParams['entity_type'] ?? 'merchant',
                    'entity_id'     => $entityId,
                    'name'          => 'myfile1.png',
                    'location'      => 'dispute/10000000000000/'. $entityId .'/myfile1.png',
                    'bucket'        => 'test_bucket',
                    'mime'          => 'text/csv',
                    'extension'     => 'csv',
                    'merchant_id'   => '10000000000000',
                    'store'         => 's3',
                ],
                [
                    'id'            => 'file_1cXSLlUU8V9sXm',
                    'type'          => 'delivery_proof',
                    'entity_type'   => $queryParams['entity_type'] ?? 'merchant',
                    'entity_id'     => $entityId,
                    'name'          => 'myfile2.pdf',
                    'location'      => 'dispute/10000000000000/'. $entityId .'/myfile2.pdf',
                    'bucket'        => 'test_bucket',
                    'mime'          => 'text/csv',
                    'extension'     => 'csv',
                    'merchant_id'   => '10000000000000',
                    'store'         => 's3',
                ],
            ],
        ];
    }

    public function getSignedUrl(string $fileId, array $params = [], $merchantId = null)
    {
        return [
            'id'            => 'file_DczOEmU9U0FFsb',
            'type'          => 'delivery_proof',
            'name'          => 'myfile2.pdf',
            'bucket'        => 'test_bucket',
            'mime'          => 'text/csv',
            'extension'     => 'csv',
            'merchant_id'   => '10000000000000',
            'store'         => 's3',
            'signed_url'    => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
        ];
    }

    public function deletefile(string $fileId) {}
}
