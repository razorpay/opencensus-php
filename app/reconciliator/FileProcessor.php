<?php

namespace Reconciliator;

use EE\Exception;
use Models\Base\UniqueIdEntity;

use ZipArchive;

class FileProcessor
{
    const FILE_NAME = 'file_name';
    const EXTENSION = 'extension';
    const MIME_TYPE = 'mime_type';
    const SIZE = 'size';
    const FILE_PATH = 'file_path';
    const DESTINATION_FOLDER = 'destination_folder';
    const FILE_TYPE = 'file_type';
    const FILE_DETAILS = 'file_details';
    const SHEET_NAME = 'sheet_name';

    const STORAGE = 'storage';
    const UPLOADED = 'uploaded';

    const EXCEL = 'excel';
    const CSV = 'csv';

    // This map should have all the extensions mentioned in Validator::ACCEPTED_EXTENSIONS_MAP
    const FILE_TYPES_MAPPINGS = [
        self::EXCEL => ['xls', 'xlsx'],
        self::CSV   => ['txt', 'csv', 'text']
    ];
    const SETTLEMENT_STORAGE_PATH = 'files/settlement';


    /********************
     * Instance objects
     ********************/
    protected $validator;


    public function __construct()
    {
        $this->validator = new Validator;
    }


    public function getFileDetails($file, $type)
    {
        assert(in_array($type, [self::STORAGE, self::UPLOADED]), "Wrong file type [Uploaded/Storage]");

        if ($type === self::UPLOADED)
        {
            $this->getUploadedFileDetails($file);
        }
        else if ($type === self::STORAGE)
        {
            $this->getStorageFileDetails($file);
        }
    }


    // TODO: Abstract out the two fileDetails methods
    public function getUploadedFileDetails($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = strtolower($file->getClientOriginalName());
        $destinationPath = storage_path(self::SETTLEMENT_STORAGE_PATH);

        $fileDetails = [
            self::FILE_NAME          => $fileName,
            self::EXTENSION          => $extension,
            self::MIME_TYPE          => $file->getMimeType(),
            self::SIZE               => $file->getClientSize(),
            self::FILE_PATH          => $destinationPath . '/' . $fileName,
            self::DESTINATION_FOLDER => $destinationPath,
        ];

        $file->move($destinationPath, $fileName);

        return $fileDetails;
    }


    public function getStorageFileDetails($file)
    {
        $mimeType = mime_content_type($file->getRealPath());

        $extension = strtolower($file->getExtension());
        $fileName = strtolower($file->getFilename());

        $fileDetails = [
            self::FILE_NAME          => $fileName,
            self::EXTENSION          => $extension,
            self::MIME_TYPE          => $mimeType,
            self::SIZE               => $file->getSize(),
            self::FILE_PATH          => $file->getRealPath(),
            self::DESTINATION_FOLDER => $file->getPath(),
        ];

        return $fileDetails;
    }


    public function deleteFileLocally($filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath);
            if ($success === false)
            {
                throw new Exception\ReconciliationException(
                    'Failed to delete file.', ['file_path' => $filePath]
                );
            }
        }
        else
        {
            throw new Exception\ReconciliationException(
                'Cannot delete. File not present.', ['file_path' => $filePath]
            );
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

        // Since there can be multiple zip files which will need to get extracted.
        $randomFolderName = UniqueIdEntity::generateUniqueId();
        $extractToPath = $this->getFolderFromFilePath($filePath) . '/' . $randomFolderName;

        // Currently supporting only zip files
        if ($extension !== 'zip')
        {
            throw new Exception\ReconciliationException(
                'Unsupported zip type. Currently supporting only zip files.', ['file_details' => $fileDetails]
            );
        }

        $this->extractZipFile($filePath, $extractToPath);

        return $extractToPath;
    }


    protected function extractZipFile($filePath, $extractToPath, $password = null)
    {
        $zip = new ZipArchive;
        $zipped = $zip->open($filePath);

        // Checking if it is actually a zipped file.
        if ($zipped === false)
        {
            throw new Exception\ReconciliationException(
                'Attempt to unzip a non-zip file.', ['file_path' => $filePath]
            );
        }
        
        //$password = 'T69801';
        // Use the password to extract if present.
        if (empty($password) === false)
        {
            $zip->setPassword($password);
        }

        // Extract to the same folder as the zip file.
        $extracted = $zip->extractTo($extractToPath);

        // Checking if it has been successfully extracted
        if ($extracted === true)
        {
            // Delete the original zip file.
            $this->deleteFileLocally($filePath);
            $zip->close();
        }
        else
        {
            throw new Exception\ReconciliationException(
                'Failed to unzip file.', ['file_path' => $filePath]
            );
        }
    }


    public function getFolderFromFilePath($filePath)
    {
        return pathinfo(realpath($filePath), PATHINFO_DIRNAME);
    }
}