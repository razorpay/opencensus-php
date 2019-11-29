<?php

namespace RZP\Models\PaperMandate;

use Config;
use Storage;
use RZP\Models\Base;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\UniqueIdEntity;

class FileUploader extends Base\Core
{
    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    public function __construct()
    {
        parent::__construct();

        $this->elfin           = $this->app['elfin'];
    }

    const JPG_EXTENSION  = '.jpg';
    const JPEG_EXTENSION = '.jpeg';
    const PDF_EXTENSION  = '.pdf';

    const IMAGE = 'image';

    const GENERATED_IMAGE_FOLDER = 'generated';
    const ENHANCED_IMAGE_FOLDER  = 'enhanced';

    const PAPER_MANDATE = 'paper_mandate';

    const PDF_MIME  = 'application/pdf';
    const JPEG_MIME = 'image/jpeg';

    const FILE_ID = 'file_id';

    public function saveCreatedMandateAndFileId(Entity $paperMandate, string $generatedMandateForm)
    {
        $fileName = $paperMandate->getPublicId() . self::PDF_EXTENSION;

        $filePath = $this->storeFileInStorage($generatedMandateForm, $fileName);

        $uploadFile = $this->createUploadedFile($filePath, $fileName, self::PDF_MIME);

        return $this->saveToUfh($uploadFile, self::GENERATED_IMAGE_FOLDER);
    }

    public function uploadEnhancedForm(Entity $paperMandate, $image)
    {
        $fileName = $paperMandate->getPublicId() . self::JPEG_EXTENSION;

        $filePath = $this->storeFileInStorage($image, $fileName);

        $uploadFile = $this->createUploadedFile($filePath, $fileName, self::JPEG_MIME);

        return $this->saveToUfh($uploadFile, self::ENHANCED_IMAGE_FOLDER);
    }

    public function saveToUfh($file, $folder)
    {
        $filenameWithoutExt = str_before($file->getClientOriginalName(), '.' . $file->getClientOriginalExtension());

        $uploadFilename = 'paper-mandate/' . $folder . '/' . $filenameWithoutExt . '_' . UniqueIdEntity::generateUniqueId();

        $file = $this->app['ufh.service']->uploadFileAndGetUrl(
            $file,
            $uploadFilename,
            self::PAPER_MANDATE,
            $this->merchant
        );

        $fileId = $file[self::FILE_ID];

        $fileId = Base\PublicEntity::stripDefaultSign($fileId);

        return $fileId;
    }

    public function getSignedUrl($fileId)
    {
        $file = $this->app['ufh.service']->getSignedUrl(
            'file_' . $fileId
        );

        return $file['signed_url'];
    }

    public function getSignedShortUrl($fileId)
    {
        $signedUrl = $this->getSignedUrl($fileId);

        return $this->elfin->shorten($signedUrl, ['ptype' => 'file'], false);
    }

    protected function createUploadedFile(string $url, $fileName, $mime): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            filesize($url),
            null,
            true
        );
    }

    protected function storeFileInStorage($base64String, $output_file)
    {
        Storage::put($output_file, base64_decode($base64String));

        return $this->getStorageDir() . $output_file;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getAdapter()->getPathPrefix();

        return $path;
    }
}