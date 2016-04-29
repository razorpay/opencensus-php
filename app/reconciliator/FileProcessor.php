<?php

namespace Reconciliator;

use EE\Exception;
use ZipArchive;

class FileProcessor
{
    const FILE_NAME = 'file_name';
    const EXTENSION = 'extension';
    const MIME_TYPE = 'mime_type';
    const SIZE = 'size';
    const FILE_PATH = 'file_path';
    const DESTINATION_FOLDER = 'destination_folder';

    const SETTLEMENT_STORAGE_PATH = 'files/settlement';

    protected $validator;

    public function __construct()
    {
        $this->validator = new Validator;
    }

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
                throw new Exception\RuntimeException(
                    'Failed to delete file: ' . $filePath);
            }
        }
        else
        {
            // TODO: Throw exception for file not found
        }
    }

    public function getTypeOfFile($file)
    {
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        $this->validator->validateExtensionMimeType($extension, $mimeType);

        return $extension;

    }

    public function unzipFile($fileDetails, $gateway)
    {
        $filePath = $fileDetails[self::FILE_PATH];
        $extension = $fileDetails[self::EXTENSION];

        // TODO: Handle password protected zip files.

        // TODO: Review security issues.

        // Currently supporting only zip files
        if ($extension === 'zip')
        {
            $this->extractZipFile($filePath);
        }
        else
        {
            // TODO: Throw error for being an unsupported zip file (g-zip, bz, etc).
        }
    }

    protected function extractZipFile($filePath, $password = null)
    {
        $zip = new ZipArchive;
        $zipped = $zip->open($filePath);

        // Checking if it is actually a zipped file.
        if ($zipped === true)
        {
            // Use the password to extract if present.
            if (empty($password) === false)
            {
                $zip->setPassword($password);
            }

            // Extract to the same folder as the zip file.
            $extracted = $zip->extractTo($this->getFolderFromFilePath($filePath));

            // Checking if it has been successfully extracted
            if ($extracted === true)
            {
                // Delete the original zip file.
                $this->deleteFileLocally($filePath);
                $zip->close();
            }
        }
        else
        {
            // TODO: Throw error for not being a zip file.
        }
    }

    public function getFolderFromFilePath($filePath)
    {
        return pathinfo(realpath($filePath), PATHINFO_DIRNAME);
    }
}