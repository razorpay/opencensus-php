<?php

namespace RZP\Reconciliator;

use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class FileProcessor
{
    const FILE_NAME               = 'file_name';
    const EXTENSION               = 'extension';
    const MIME_TYPE               = 'mime_type';
    const SIZE                    = 'size';
    const FILE_PATH               = 'file_path';
    const DESTINATION_FOLDER      = 'destination_folder';
    const FILE_TYPE               = 'file_type';
    const FILE_DETAILS            = 'file_details';
    const SHEET_NAME              = 'sheet_name';

    const ZIP_EXTENSION           = 'zip';

    /**
     * This is used when trying to get the file details of files
     * which are present on disk already.
     */
    const STORAGE                 = 'storage';
    /**
     * This is used when trying to get the file details of files
     * which are being sent over the network. The file type of
     * these files would be UploadedFile.
     */
    const UPLOADED                = 'uploaded';

    const EXCEL                   = 'excel';
    const CSV                     = 'csv';

    // This map should have all the extensions mentioned in Validator::ACCEPTED_EXTENSIONS_MAP
    const FILE_TYPES_MAPPINGS     = [
        self::EXCEL => ['xls', 'xlsx'],
        self::CSV   => ['txt', 'csv', 'text']
    ];

    const SETTLEMENT_STORAGE_PATH = 'files/settlement';

    /********************
     * Instance objects
     ********************/
    protected $validator;
    protected $messenger;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->messenger = new Messenger();
    }

    public function getFileDetails($file, $type = self::UPLOADED)
    {
        assertTrue(in_array($type, [self::STORAGE, self::UPLOADED]), "Wrong file type [Uploaded/Storage]");

        if ($type === self::UPLOADED)
        {
            return $this->getUploadedFileDetails($file);
        }
        else
        {
            return $this->getStorageFileDetails($file);
        }
    }

    /**
     * This method is used to get the file details of files received through a route directly
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function getUploadedFileDetails($file)
    {
        $fileName = strtolower($file->getClientOriginalName());
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $size = $file->getClientSize();
        $sourceFolderPath = storage_path(self::SETTLEMENT_STORAGE_PATH);
        $filePath = $sourceFolderPath . '/' . $fileName;

        $file->move($sourceFolderPath, $fileName);

        return $this->fileDetailsToArray($fileName, $extension, $mimeType, $size, $sourceFolderPath, $filePath);
    }

    /**
     * This methods is used to get the file details of files which are already present on the storage.
     * getUploadedFileDetails() cannot be used because the file object class is different here.
     *
     * @param $file
     * @return array
     */
    protected function getStorageFileDetails($file)
    {
        $fileName = strtolower($file->getFilename());
        $extension = strtolower($file->getExtension());
        $mimeType = mime_content_type($file->getRealPath());
        $size = $file->getSize();
        $sourceFolderPath =  $file->getPath();
        $filePath = $file->getRealPath();

        return $this->fileDetailsToArray($fileName, $extension, $mimeType, $size, $sourceFolderPath, $filePath);
    }

    /**
     * @param string $fileName String name of the file to be stored
     * @param string $extension String extension of the file
     * @param string $mimeType String mimetype of the type
     * @param int $size Integer Size of the file
     * @param string $sourceFolderPath Path of the folder in which the file is present
     * @param string $filePath Full path of the file
     * @return array
     */
    protected function fileDetailsToArray($fileName, $extension, $mimeType, $size, $sourceFolderPath, $filePath)
    {
        $fileDetails = [
            self::FILE_NAME          => $fileName,
            self::EXTENSION          => $extension,
            self::MIME_TYPE          => $mimeType,
            self::SIZE               => $size,
            self::DESTINATION_FOLDER => $sourceFolderPath,
            self::FILE_PATH          => $filePath,
        ];

        return $fileDetails;
    }

    public function deleteFileLocally($filePath)
    {
        if (file_exists($filePath) === false)
        {
            // Critical alert because this should ideally never happen.
            $this->messenger->raiseReconAlert(
                [ 'trace_code' => TraceCode::RECON_FILE_DELETE_FAILURE,
                  'message'    => 'File not present, to delete locally.',
                  'file_path'  => $filePath
                ]);
            return;
        }

        $success = unlink($filePath);

        if ($success === false)
        {
            $this->messenger->raiseReconAlert(
                [ 'trace_code' => TraceCode::RECON_FILE_DELETE_FAILURE,
                  'message'    => 'Unable to delete the file, locally.',
                  'file_path'  => $filePath
                ]);
        }
    }

    /**
     * Gets the extension of the file.
     * Also validates the (mime type + extension) combination.
     *
     * @param UploadedFile $file
     * @return string Extension of the file
     */
    public function getTypeOfFile($file)
    {
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        // Validates the mime type + extension.
        $this->validator->validateExtensionMimeType($extension, $mimeType);

        return $extension;
    }

    /**
     * Unzips the file to a folder which is created in the same folder in which the zip file is present.
     *
     * @param array $fileDetails
     * @param string $password Password to unlock the zip file.
     * @return string The folder path of the extracted file
     * @throws Exception\ReconciliationException
     */
    public function unzipFile($fileDetails, $password = null)
    {
        $filePath = $fileDetails[self::FILE_PATH];
        $extension = $fileDetails[self::EXTENSION];

        // TODO: Review zip files security.

        // Since there can be multiple zip files which will need to get extracted,
        // will be storing each zip file's extracted files in a separate directory.
        $randomFolderName = UniqueIdEntity::generateUniqueId();
        $extractToPath = $this->getFolderFromFilePath($filePath) . '/' . $randomFolderName;

        // Currently supporting only zip files
        // When other types of zip needs to be supported,
        // handle for each type separately using the conditional statements.
        if ($extension !== self::ZIP_EXTENSION)
        {
            throw new Exception\ReconciliationException(
                'Unsupported zip type. Currently supporting only zip files.', ['file_details' => $fileDetails]
            );
        }

        // Extracts the zip file to the given path.
        $this->extractZipFile($filePath, $extractToPath, $password);

        return $extractToPath;
    }

    /**
     * Extracts the given zip file to a given extract location. Throws an exception if unable to extract.
     *
     * @param string $filePath The complete file path of the zip file that needs to be extracted
     * @param string $extractToPath The folder path to where the zip file needs to be extracted
     * @param string $password Optional Password for the zip file, if present
     * @throws Exception\ReconciliationException
     */
    protected function extractZipFile($filePath, $extractToPath, $password)
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

        // Use the password to extract if present.
        if (empty($password) === false)
        {
            $zip->setPassword($password);
        }

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
                'Failed to unzip file.',
                [
                    'file_path'      => $filePath,
                    'status_message' => $zip->getStatusString(),
                ]
            );
        }
    }

    public function getFolderFromFilePath($filePath)
    {
        return pathinfo(realpath($filePath), PATHINFO_DIRNAME);
    }
}
