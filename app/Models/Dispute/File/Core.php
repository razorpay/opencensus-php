<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Dispute\Entity as DisputeEntity;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function create(DisputeEntity $dispute, array $input): array
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

        return $file->toArrayPublic();
    }

    public function createForDispute(string $disputeId, array $fileUrls): array
    {
        $dispute = $this->repo->dispute->findByPublicId($disputeId);

        $disputeFiles = [];

        foreach ($fileUrls as $fileUrl)
        {
            $input = [
                Entity::DISPUTE_ID      => DisputeEntity::stripDefaultSign($disputeId),
                Entity::URL             => $fileUrl,
            ];

            $fileArray = $this->create($dispute, $input);

            array_push($disputeFiles, $fileArray);
        }

        return $disputeFiles;
    }

    public function uploadFiles(string $disputeId, array $files): array
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILE_CREATE,
            [
                'id'          => $disputeId,
                'files'       => $files,
            ]);

        $validator = new Validator();

        $fileUrls = [];

        $validator->validateNumberOfFiles($files);

        foreach ($files as $file)
        {
            $validator->validateFileDetails($file);

            $url = $this->uploadFileAndGetUrl($file);

            array_push($fileUrls, $url);
        }

        $disputeFiles = $this->createForDispute($disputeId, $fileUrls);

        return $disputeFiles;
    }
}
