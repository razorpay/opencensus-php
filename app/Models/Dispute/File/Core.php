<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Dispute\Entity as DisputeEntity;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function create(DisputeEntity $dispute, array $input)
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILE_CREATE,
            [
                'input'       => $input,
                'dispute_id'  => $dispute->getId(),
            ]);

        $file = (new Entity)->build($input);

        $file->generateId();

        $file->dispute()->associate($dispute);

        $this->repo->saveOrFail($file);

        return $file;
    }

    protected function uploadAndCreateFile(DisputeEntity $dispute, array $fileInput)
    {
        $url = $this->uploadFileAndGetUrl($fileInput[Entity::FILE]);

        $input = [
            Entity::DISPUTE_ID          => $dispute->getId(),
            Entity::URL                 => $url,
            Entity::NAME                => $fileInput[Entity::NAME],
            Entity::CATEGORY            => $fileInput[Entity::CATEGORY],
        ];

        $file = $this->create($dispute, $input);

        return $file;
    }

    public function checkFileInput(array $files): array
    {
        $validator = new Validator();

        $validator->validateFilesInput($files);

        foreach ($files as $fileInput)
        {
            $validator->validateFileDetails($fileInput);
        }

        return $files;
    }

    public function uploadFiles(DisputeEntity $dispute, array $files): array
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILES_UPLOAD,
            [
                'id'          => $dispute->getId(),
                'files_count' => sizeof($files),
            ]);

        $disputeFiles = [];

        foreach ($files as $fileInput)
        {
            $file = $this->uploadAndCreateFile($dispute, $fileInput);

            array_push($disputeFiles, $file->toArrayPublic());
        }

        return $disputeFiles;
    }
}
