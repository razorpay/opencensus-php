<?php

namespace RZP\Services\Mock;

use Config;
use RZP\Trace\TraceCode;
use RZP\Services\UfhService as BaseUfhClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UfhService extends BaseUfhClient
{
    const MOCK_FILE_ID      = 'file_1cXSLlUU8V9sXl';

    const DISPUTE_EVIDENCE_FILE_IDS = [
        'file_shippingProfId',
        'file_billingProfId1',
        'file_billingProfId2',
        'file_cancelProofId1',
        'file_explnationProf',
        'file_customType1Id1',
        'file_customType1Id2',
        'file_customType2Id1',
        'file_customType3Id1',
    ];

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
            'id'           => self::MOCK_FILE_ID,
            'type'         => $type,
            'name'         => $storageFileName,
            'created_at'   => time(),
            'mime'         => 'image/png',
            'location'     => $storageFileName,
            'size'         => 12345,
            'display_name' => $requestData['display_name'],
        ];
    }

    public function fetchFiles(array $queryParams, $merchantId = null): array
    {
        $entityId = $queryParams['entity_id'] ?? 'id1';
        $ids = $queryParams['ids'] ?? [];

        $type = null;
        $id0 = 'file_1cXSLlUU8V9sXl';

        if (empty($ids) === false)
        {
            if ($this->isDisputeEvidenceFileId($ids[0]))
            {
                $type = 'dispute_evidence';
                $id0 = $ids[0];
            }
        }

        return [
            'entity'  => 'collection',
            'count'   => 2,
            'items'   => [
                [
                    'id'            => $id0,
                    'type'          => $type ?? 'explanation_letter',
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
                    'type'          =>  $type ?? 'delivery_proof',
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
            'id'            => $fileId,
            'type'          => $this->isDisputeEvidenceFileId($fileId) ? 'dispute_evidence' : 'delivery_proof',
            'name'          => 'myfile2.pdf',
            'bucket'        => 'test_bucket',
            'mime'          => 'text/csv',
            'extension'     => 'csv',
            'merchant_id'   => '10000000000000',
            'store'         => 's3',
            'signed_url'    => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
        ];
    }

    public function deletefile(string $fileId, string $merchantId = null, string $type =null) {}


    protected function isDisputeEvidenceFileId($fileId): string
    {
        return (in_array($fileId, self::DISPUTE_EVIDENCE_FILE_IDS, true) === true);
    }
}
