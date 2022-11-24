<?php


namespace RZP\Models\BankingAccount\Activation\MIS;

use App;
use File;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class Base extends BaseCore
{
    use FileHandlerTrait;

    protected $fileName;

    protected $fileType;

    protected $input;

    protected $entity;

    public function __construct(array $input)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->auth = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->input = $input;
    }

    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.
        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $error, $test);

        return $object;
    }

    protected function uploadTemporaryFileToStore(string $pathToTemporaryFile)
    {
        $ufhService = $this->app['ufh.service'];

        $uploadedFileInstance = $this->getUploadedFileInstance($pathToTemporaryFile);

        $response = $ufhService->uploadFileAndGetUrl($uploadedFileInstance,
            $name = File::name($pathToTemporaryFile),
            $this->fileType,
            null);

        $this->trace->info(
            TraceCode::UFH_RESPONSE,
            [
                'response'           => $response,
            ]);

        return $response;
    }

    public function createFile(array $fileInput)
    {
        $xlsxFilePath = $this->createExcelFile($fileInput, $this->fileName, "/tmp/");

        return $this->uploadTemporaryFileToStore($xlsxFilePath);
    }

    public function generateFile(array $fileInput)
    {
        $response = $this->createFile($fileInput);

        $ufhService = $this->app['ufh.service'];

        $signedUrlResponse = $ufhService->getSignedUrl($response['file_id']);

        return $signedUrlResponse;
    }

    abstract public function getFileInput();

    public function generate()
    {
        $fileInput = $this->getFileInput();

        return $this->generateFile($fileInput);
    }
}
