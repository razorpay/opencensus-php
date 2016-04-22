<?php

namespace Reconciliator;

use EE\Exception;

class FileProcessor
{
    public function getFileDetails($file)
    {
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        $destinationPath = storage_path('files/settlement');

        $fileDetails = [
            'file_name'          => $fileName,
            'extension'          => $extension,
            'mime_type'          => $mimeType,
            'size'               => $file->getClientSize(),
            'file_path'          => $destinationPath . '/' . $fileName,
            'destination_folder' => $destinationPath,
        ];

        $file->move($destinationPath, $fileName);
        
        return $fileDetails;
    }

    public function deleteFileLocally($fileDetails)
    {
        $filePath = $fileDetails['file_path'];

        if (file_exists($filePath))
        {
            $success = unlink($filePath);
            if ($success === false)
            {
                // TODO: Throw exception for file not found
                throw new Exception\RuntimeException(
                    'Failed to delete file: ' . $filePath);
            }
        }
    }
}