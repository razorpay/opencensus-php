<?php

namespace RZP\Reconciliator;

use App;
use RZP\Http\Request\Requests;
use SplFileInfo;
use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use Storage;

use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\FileStore\Utility;
use RZP\Models\Base\UniqueIdEntity;

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
    const SHEET_COUNT             = 'sheet_count';

    const ZIP_EXTENSION           = 'zip';
    const SEVEN_Z                 = '7z';

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

    // This map should have all the mime_types mentioned in Validator::ACCEPTED_EXTENSIONS_MAP
    const FILE_TYPES_MAPPINGS = [
        self::EXCEL => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
            'application/excel',
            'application/vnd.ms-excel',
            'application/msexcel',
            'application/vnd.ms-office',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/cdfv2-unknown'
        ],
        self::CSV => [
            'text/csv',
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'text/plain',
            'text/x-algol68'
        ]
    ];

    /******************************
     * csv file handling constants
     ******************************/

    const LINES_FROM_TOP    = 'lines_from_top';
    const LINES_FROM_BOTTOM = 'lines_from_bottom';

    /********************
     * Instance objects
     ********************/
    protected $validator;
    protected $messenger;
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->validator = new Validator;
        $this->messenger = new Messenger();

        $this->trace = $app['trace'];

        $this->registerMimeTypeGuesser();
    }

    /**
     * We do this because two guessers are registered by default:
     *   - FileBinaryMimeTypeGuesser
     *   - FileinfoMimeTypeGuesser
     * FileinfoMimeTypeGuesser is given the higher preference.
     * To give FileBinaryMimeTypeGuesser the higher preference,
     * we have to re-register it like a custom guesser.
     * Check Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser class
     * for more information around this.
     *
     * FileBinaryMimeTypeGuesser seems to be better guesser of the two.
     * It runs the command `file -b --mime %s` to get the mime type.
     * For some text files, `FileinfoMimeTypeGuesser` gives application/zlib and
     * `FileBinaryMimeTypeGuesser` gives text/plain (correct!)
     */
    protected function registerMimeTypeGuesser()
    {
        $guesser = new MimeTypes();

        $guesser->registerGuesser(new FileBinaryMimeTypeGuesser());
    }

    public function getFileDetails($file, $type = self::UPLOADED, bool $move = true)
    {
        assertTrue(in_array($type, [self::STORAGE, self::UPLOADED]), "Wrong file type [Uploaded/Storage]");

        if ($type === self::UPLOADED)
        {
            return $this->getUploadedFileDetails($file, $move);
        }
        else
        {
            return $this->getStorageFileDetails($file);
        }
    }

    public function getFileName(SplFileInfo $file)
    {
        return $file->getFilename();
    }

    /**
     * Downloads and stores the file in the storage directory
     *
     * @param string $link
     *
     * @return SplFileInfo
     */
    public function getAndStoreFileFromLink(string $link)
    {
        $request = [
            'url'     => stripcslashes($link),
            'method'  => 'GET',
            'headers' => [],
            'content' => [],
            'options' => [
                'timeout'          => 60,
                'follow_redirects' => true,
                'verify'           => true,
            ],
        ];

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        $contentType = $response->headers->getValues('Content-Type')[0];

        $extension = $this->validator->getExtensionFromContentType($contentType);

        $fileName = (string) time() . '.' . $extension;

        $filePath = Utility::getStorageDir();

        $filePath .= '/' . $fileName;

        Storage::disk('settlements')->put($fileName, $response->body);

        return new SplFileInfo($filePath);
    }

    public function isZipFile($file, string $fileLocationType): bool
    {
         $fileExtension = $this->getFileExtension($file, $fileLocationType);

         return (in_array($fileExtension, Validator::SUPPORTED_ZIP_EXTENSIONS, true) === true);
    }

    /**
     * Unzips the file to a folder which is created in the same folder in which the zip file is present.
     *
     * @param array  $fileDetails
     * @param string $password Password to unlock the zip file.
     * @param bool   $use7z
     *
     * @return string The folder path of the extracted file
     * @throws Exception\ReconciliationException
     */
    public function unzipFile($fileDetails, $password = null, bool $use7z = false)
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
        if (($extension !== self::ZIP_EXTENSION) and ($extension !== self::SEVEN_Z))
        {
            throw new Exception\ReconciliationException(
                'Unsupported zip type. Currently supporting only zip files.', ['file_details' => $fileDetails]
            );
        }

        // Extracts the zip file to the given path.
        $this->extractZipFile($filePath, $extractToPath, $password, $use7z);

        return $extractToPath;
    }

    public function deleteFileLocally($filePath)
    {
        $this->trace->info(
            TraceCode::FILE_DELETING,
            [
                'file_path' => $filePath
            ]);

        if (file_exists($filePath) === false)
        {
            // // Critical alert because this should ideally never happen.
            // $this->messenger->raiseReconAlert(
            //     [ 'trace_code' => TraceCode::RECON_FILE_DELETE_FAILURE,
            //       'message'    => 'File not present, to delete locally.',
            //       'file_path'  => $filePath
            //     ]);

            // We sometimes delete the file and then
            // again try to delete the file. Sorry.
            return;
        }

        $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

        if ($success === false)
        {
            $this->messenger->raiseReconAlert(
                [ 'trace_code' => TraceCode::RECON_FILE_DELETE_FAILURE,
                  'message'    => 'Unable to delete the file, locally.',
                  'file_path'  => $filePath
                ]);
        }
    }

    public function deleteDirectoryLocally($dir)
    {
        $this->trace->info(
            TraceCode::DIRECTORY_DELETING,
            [
                'dir_path' => $dir
            ]);

        //
        // If someone sends file instead of a directory to this function.
        //
        if (is_dir($dir) === false)
        {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        $this->trace->info(
            TraceCode::FILES_DELETING,
            [
                'file_paths' => $files,
            ]);

        foreach ($files as $file)
        {
            $this->trace->info(
                TraceCode::FILE_DELETING,
                [
                    'file_path' => $file
                ]);

            if (is_dir("$dir/$file") === true)
            {
                $this->deleteDirectoryLocally("$dir/$file");
            }
            else
            {
                unlink("$dir/$file"); // nosemgrep : php.lang.security.unlink-use.unlink-use
            }
        }

        $success = rmdir($dir);

        if ($success === false)
        {
            $this->messenger->raiseReconAlert(
                [ 'trace_code' => TraceCode::RECON_FILE_DELETE_FAILURE,
                  'message'    => 'Unable to delete the DIR, locally.',
                  'dir_path'  => $dir
                ]);
        }
    }

    /**
     * Gets the extension of the file.
     * Also validates the (mime type + extension) combination.
     *
     * @param UploadedFile $file
     * @param              $fileLocationType
     *
     * @return string Extension of the file
     */
    protected function getFileExtension($file, $fileLocationType)
    {
        if ($fileLocationType === self::UPLOADED)
        {
            $mimeType = $file->getMimeType();

            $extension = $file->getClientOriginalExtension();
        }
        else
        {
            $mimeType = mime_content_type($file->getRealPath());

            $extension = $file->getExtension();
        }

        // Validates the mime type + extension.
        $this->validator->validateExtensionMimeType($extension, $mimeType);

        return $extension;
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

    /**
     * This method is used to get the file details of files received through a route directly
     *
     * @param UploadedFile $file
     * @param bool         $move This is used when we are trying to get the input
     *                           file details of a file directly uploaded from the
     *                           request. Here, we don't want to move the file around.
     *
     * @return array
     */
    protected function getUploadedFileDetails(UploadedFile $file, bool $move = true)
    {
        $fileName = strtolower($file->getClientOriginalName());
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType());
        $size = $file->getSize();

        if ($move === true)
        {
            $sourceFolderPath = Utility::getStorageDir();

            $filePath = $sourceFolderPath . '/' . $fileName;

            $file->move($sourceFolderPath, $fileName);
        }
        else
        {
            $sourceFolderPath = $file->getPath();
            $filePath = $file->getRealPath();
        }

        return $this->fileDetailsToArray($fileName, $extension, $mimeType, $size, $sourceFolderPath, $filePath);
    }

    /**
     * This methods is used to get the file details of files which are already present on the storage.
     * getUploadedFileDetails() cannot be used because the file object class is different here.
     *
     * @param $file
     * @return array
     */
    protected function getStorageFileDetails(SplFileInfo $file)
    {
        $fileName = strtolower($file->getFilename());
        $extension = strtolower($file->getExtension());
        $filePath = $file->getRealPath();
        $mimeType = strtolower(mime_content_type($filePath));
        $size = $file->getSize();
        $sourceFolderPath =  $file->getPath();

        return $this->fileDetailsToArray($fileName, $extension, $mimeType, $size, $sourceFolderPath, $filePath);
    }

    /**
     * Extracts the given zip file to a given extract location. Throws an exception if unable to extract.
     *
     * @param string $filePath      The complete file path of the zip file that needs to be extracted
     * @param string $extractToPath The folder path to where the zip file needs to be extracted
     * @param string $password      Optional Password for the zip file, if present
     * @param bool   $use7z
     */
    protected function extractZipFile($filePath, $extractToPath, $password, $use7z)
    {
        try
        {
            if ($use7z === true)
            {
                $this->extractUsing7z($filePath, $extractToPath, $password);
            }
            else
            {
                $this->extractUsingPhpZipArchive($filePath, $extractToPath, $password);
            }
        }
        finally
        {
            // Delete the original zip file.
            $this->deleteFileLocally($filePath);
        }
    }

    protected function extractUsing7z($filePath, $extractToPath, $password)
    {
        $cmdPath = '';

        if (PHP_OS === 'Darwin')
        {
            $cmdPath = '/usr/local/Cellar/p7zip/16.02/bin/';
        }

        $cmd = escapeshellcmd(
                    $cmdPath .
                    '7z x' .
                    ' -P' . escapeshellarg($password) .
                    ' -o' . escapeshellarg($extractToPath) .
                    ' ' . escapeshellarg($filePath));

        exec($cmd, $unzipOutput, $status); // nosemgrep : php.lang.security.exec-use.exec-use

        if ($status !== 0)
        {
            //
            // Creates a dummy file in the extractToPath
            // even when it's not able to extract.
            // *facepalm*
            //
            $this->deleteDirectoryLocally($extractToPath);

            throw new Exception\ReconciliationException(
                'Failed to unzip file.',
                [
                    'file_path'      => $filePath,
                    'extract_path'   => $extractToPath,
                    'status_message' => $status,
                    'response'       => $unzipOutput
                ]);
        }
    }

    protected function extractUsingPhpZipArchive($filePath, $extractToPath, $password)
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
            $zip->close();
        }
        else
        {
            //
            // Creates a dummy file in the extractToPath
            // even when it's not able to extract.
            // *facepalm*
            //
            $this->deleteDirectoryLocally($extractToPath);

            throw new Exception\ReconciliationException(
                'Failed to unzip file.',
                [
                    'file_path'      => $filePath,
                    'status_message' => $zip->getStatusString(),
                ]);
        }
    }

    protected function getFolderFromFilePath($filePath)
    {
        return pathinfo(realpath($filePath), PATHINFO_DIRNAME);
    }
}
