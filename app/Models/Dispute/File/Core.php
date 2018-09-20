<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Dispute\Entity as DisputeEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Core extends Base\Core
{
    const FILE_ID             = 'file_id';
    const NAME                = 'name';
    const CATEGORY            = 'category';

    // Keys for array name of uploaded documents
    const FILES               = 'upload_files';

    const FILE                = 'file';
    const ALL_FILES           = 'files';

    protected function uploadAndCreateFile(DisputeEntity $dispute, array $fileInput)
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILES_UPLOAD,
            [
                'id'   => $dispute->getId(),
                'file' => array_except($fileInput, self::FILE),
            ]);

        $file = $fileInput[self::FILE];

        $this->app['ufh.service']->uploadFileAndGetUrl(
            $file,
            $this->getStorageFileName($dispute, $file),
            $fileInput[self::CATEGORY],
            $dispute);
    }

    public function checkFilesInput(array $files): array
    {
        $validator = new Validator();

        $validator->validateFilesInput($files);

        foreach ($files as $fileInput)
        {
            $validator->validateFileDetails($fileInput);
        }

        return $files;
    }

    public function uploadFiles(DisputeEntity $dispute, array $files)
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILES_UPLOAD,
            [
                'id'          => $dispute->getId(),
                'files_count' => count($files),
            ]);

        foreach ($files as $fileInput)
        {
            $this->uploadAndCreateFile($dispute, $fileInput);
        }
    }

    public function getFilesForEntity(PublicEntity $entity): array
    {
        $queryParams = [
            'entity_type'   => $entity->getEntityName(),
            'entity_id'     => $entity->getId(),
        ];

        return $this->getFiles($queryParams);
    }

    public function getFiles(array $queryParams): array
    {
        return $this->app['ufh.service']->fetchFiles($queryParams);
    }

    protected function getStorageFileName(DisputeEntity $dispute, UploadedFile $file): string
    {
        $nameWithoutExtension = str_replace('.' . $file->getClientOriginalExtension(),
                                            '',
                                            $file->getClientOriginalName());

        return $dispute->getEntityName() . '/' . $dispute->merchant->getPublicId() . '/' .
               $dispute->getPublicId() . '/' . $nameWithoutExtension;
    }

    public function deleteFile(DisputeEntity $dispute, string $fileId)
    {
        (new Validator)->validateDisputeForFileDelete($dispute);

        $this->app['ufh.service']->deletefile($fileId);
    }
}
