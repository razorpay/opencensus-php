<?php

namespace RZP\Models\Dispute\File;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Dispute\Entity as DisputeEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Core extends Base\Core
{
    public function create(DisputeEntity $dispute, array $input): array
    {
        $this->trace->info(
            TraceCode::DISPUTE_FILE_CREATE,
            [
                'input'       => $input,
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

    protected function uploadFileAndGetUrl(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        $fileName = UniqueIdEntity::generateUniqueId() . '.' . $extension;

        $destinationPath = storage_path(Entity::STORAGE_PATH);

        $mimeType = $file->getMimeType();

        $fileDetails = [
                'file_name'  => $fileName,
                'extension'  => $extension,
                'mime_type'  => $mimeType,
                'size'       => $file->getClientSize(),
                'file_path'  => $destinationPath . '/' . $fileName,
        ];

        $this->trace->info(
            TraceCode::DISPUTE_FILE_DETAILS,
            $fileDetails
        );

        // Moves locally.
        $file->move($destinationPath, $fileName);

        try
        {
            // Store the file in AWS
            $fileUrl = (new StorageClient())->saveToStorage($fileDetails);
        }
        catch (Exception\BaseException $e)
        {
            $fileUrl = '';

            $e->setData($fileDetails);

            throw $e;
        }
        finally
        {
            $this->deleteFile($destinationPath . '/' . $fileName);
        }

        return $fileUrl;
    }

    protected function deleteFile($filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath);

            if ($success === false)
            {
                throw new Exception\RuntimeException(
                    'Failed to delete file: ' . $filePath);
            }
        }
    }
}
