<?php

namespace RZP\Models\Dispute\File;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileHandlerTrait
{
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
