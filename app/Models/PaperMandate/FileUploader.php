<?php

namespace RZP\Models\PaperMandate;

use Config;
use Storage;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Services\UfhService;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\UniqueIdEntity;

class FileUploader extends Base\Core
{
    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    /**
     * @property Entity $paperMandate
     */
    protected $paperMandate;

    public function __construct(Entity $paperMandate)
    {
        parent::__construct();

        $this->elfin           = $this->app['elfin'];

        $this->paperMandate    = $paperMandate;
    }

    const JPG_EXTENSION  = '.jpg';
    const JPEG_EXTENSION = '.jpeg';
    const PDF_EXTENSION  = '.pdf';

    const IMAGE = 'image';

    const GENERATED_IMAGE_FOLDER = 'generated';
    const ENHANCED_IMAGE_FOLDER  = 'enhanced';
    const UPLOADED_IMAGE_FOLDER  = 'uploaded';

    const PAPER_MANDATE = 'paper_mandate';

    const PDF_MIME  = 'application/pdf';
    const JPEG_MIME = 'image/jpeg';

    const FILE_ID = 'file_id';

    public function saveCreatedMandateAndFileId(string $generatedMandateForm)
    {
        $fileName = $this->paperMandate->getPublicId() . self::PDF_EXTENSION;

        $filePath = $this->storeFileInStorage($generatedMandateForm, $fileName);

        $uploadFile = $this->createUploadedFile($filePath, $fileName, self::PDF_MIME);

        return $this->saveToUfh($uploadFile, self::GENERATED_IMAGE_FOLDER);
    }

    public function uploadEnhancedForm($image)
    {
        $fileName = $this->paperMandate->getPublicId() . self::JPEG_EXTENSION;

        $filePath = $this->storeFileInStorage($image, $fileName);

        $uploadFile = $this->createUploadedFile($filePath, $fileName, self::JPEG_MIME);

        return $this->saveToUfh($uploadFile, self::ENHANCED_IMAGE_FOLDER);
    }

    public function uploadUploadedForm($image)
    {
        $timeStarted = microtime(true);

        $fileName = $this->paperMandate->getPublicId() . self::JPEG_EXTENSION;

        $filePath = $this->compressUploadedImage($fileName, $image);

        $uploadFile = $this->createUploadedFile($filePath, $fileName, self::JPEG_MIME);

        $uploadFileId = $this->saveToUfh($uploadFile, self::UPLOADED_IMAGE_FOLDER);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::PAPER_MANDATE_STORE_UPLOADED_FILE,
            [
                'paper_mandate_id' => $this->paperMandate->getPublicId(),
                'time_taken'       => $timeTaken
            ]
        );

        return $uploadFileId;
    }

    public function saveToUfh($file, $folder)
    {
        $filenameWithoutExt = str_before($file->getClientOriginalName(), '.' . $file->getClientOriginalExtension());

        $uploadFilename = 'paper-mandate/' . $folder . '/' . $filenameWithoutExt . '_' . UniqueIdEntity::generateUniqueId();

        $ufhResponse = $this->app['ufh.service']->uploadFileAndGetUrl(
            $file,
            $uploadFilename,
            self::PAPER_MANDATE,
            $this->merchant
        );

        $localMovedFile = $ufhResponse[UfhService::LOCAL_FILE];

        $this->deleteFile($localMovedFile->getRealPath());

        $fileId = $ufhResponse[UfhService::FILE_ID];

        $fileId = Base\PublicEntity::stripDefaultSign($fileId);

        return $fileId;
    }

    public function getSignedUrl($fileId, $duration = 15)
    {
        if (empty($fileId) === true)
        {
            return null;
        }

        $file = $this->app['ufh.service']->getSignedUrl(
            'file_' . $fileId,
            ['duration' => $duration],
            $this->paperMandate->merchant->getId()
        );

        return $file['signed_url'];
    }

    public function getSignedShortUrl($fileId, $duration = 15)
    {
        $signedUrl = $this->getSignedUrl($fileId, $duration);

        return $this->elfin->shorten($signedUrl, ['ptype' => 'file'], false);
    }

    public function getShortUrl($url, $duration = 15)
    {
        return $this->elfin->shorten($url, ["allowed_keys" => [9,10]], false);
    }

    protected function createUploadedFile(string $url, $fileName, $mime): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true
        );
    }

    protected function storeFileInStorage($base64String, $output_file)
    {
        Storage::put($output_file, base64_decode($base64String));

        return $this->getStorageDir() . $output_file;
    }

    protected function deleteFile($filePath)
    {
        if (($filePath !== null) and (file_exists($filePath) === true))
        {
            $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                $this->trace->critical(TraceCode::NACH_FILE_DELETE_ERROR, ['file_path' => $filePath]);
            }
        }
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->path('');

        return $path;
    }

    protected function compressUploadedImage($fileName, $image)
    {
        $info = getimagesize($image);

        $imageCompressed = null;

        switch ($info['mime'])
        {
            case 'image/jpeg':
                $imageCompressed = imagecreatefromjpeg($image);
                break;
            case 'image/jpg':
                $imageCompressed = imagecreatefromjpeg($image);
                break;
            case 'image/png':
                $imageCompressed = imagecreatefrompng($image);
                break;
        }

        ob_start();
        imagejpeg($imageCompressed, null, 50);
        $imagedata = ob_get_clean();

        Storage::put($fileName, $imagedata);

        $filePath = $this->getStorageDir() . $fileName;

        return $filePath;
    }
}
