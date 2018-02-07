<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Services\UfhService;
use RZP\Models\Dispute\Entity as DisputeEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Core extends Base\Core
{
    public function create(DisputeEntity $dispute, array $input)
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILE_CREATE,
            [
                'input'       => $input,
                'dispute_id'  => $dispute->getId(),
            ]);

        $file = (new Entity)->build($input);

        $file->dispute()->associate($dispute);

        $this->repo->saveOrFail($file);

        return $file;
    }

    protected function uploadAndCreateFile(DisputeEntity $dispute, array $fileInput)
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILES_UPLOAD,
            [
                'id'   => $dispute->getId(),
                'file' => array_except($fileInput, Entity::FILE),
            ]);

        $file = $fileInput[Entity::FILE];

        $uploadedFileDetails = $this->app['ufh.service']->uploadFileAndGetUrl(
                                                            $file,
                                                            $this->getStorageFileName($dispute, $file),
                                                            $fileInput[Entity::CATEGORY],
                                                            $dispute);

        $input = [
            Entity::FILE_ID    => $uploadedFileDetails[UfhService::FILE_ID],
            Entity::NAME       => $fileInput[Entity::NAME],
            Entity::CATEGORY   => $fileInput[Entity::CATEGORY],
        ];

        $file = $this->create($dispute, $input);

        return $file;
    }

    public function checkFilesInput(array $files): array
    {
        $validator = new Validator();

        $validator->validateFilesInput($files);

        foreach ($files as $fileInput)
        {
            $myfile = $fileInput[Entity::FILE];

            $this->printFile($myfile);

            $validator->validateFileDetails($fileInput);
        }

        return $files;
    }

    public function printFile(UploadedFile $myFile)
    {
        s($myFile->getMimeType(), $myFile->getClientMimeType());
    }

    public function uploadFiles(DisputeEntity $dispute, array $files): array
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILES_UPLOAD,
            [
                'id'          => $dispute->getId(),
                'files_count' => count($files),
            ]);

        $disputeFiles = [];

        foreach ($files as $fileInput)
        {
            $file = $this->uploadAndCreateFile($dispute, $fileInput);

            $disputeFiles[] = $file->toArrayPublic();
        }

        return $disputeFiles;
    }

    protected function getStorageFileName(DisputeEntity $dispute, UploadedFile $file): string
    {
        $nameWithoutExtension = str_replace('.' . $file->getClientOriginalExtension() ,
                                            '' ,
                                            $file->getClientOriginalName());

        return $dispute->getEntityName() . '/' . $dispute->merchant->getPublicId() . '/' .
                          $dispute->getPublicId() . '/' . $nameWithoutExtension;
    }
}
