<?php

namespace Reconciliator;

use EE\Exception;

class FileProcessor
{
    const FILE_NAME = 'file_name';
    const EXTENSION = 'extension';
    const MIME_TYPE = 'mime_type';
    const SIZE = 'size';
    const FILE_PATH = 'file_path';
    const DESTINATION_FOLDER = 'destination_folder';
    const SETTLEMENT_STORAGE_PATH = 'files/settlement';
    
    public function getFileDetails($file)
    {
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        $destinationPath = storage_path(self::SETTLEMENT_STORAGE_PATH);

        $fileDetails = [
            self::FILE_NAME          => $fileName,
            self::EXTENSION          => $extension,
            self::MIME_TYPE          => $mimeType,
            self::SIZE               => $file->getClientSize(),
            self::FILE_PATH          => $destinationPath . '/' . $fileName,
            self::DESTINATION_FOLDER => $destinationPath,
        ];

        $file->move($destinationPath, $fileName);
        
        return $fileDetails;
    }

    public function deleteFileLocally($fileDetails)
    {
        $filePath = $fileDetails[self::FILE_PATH];

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