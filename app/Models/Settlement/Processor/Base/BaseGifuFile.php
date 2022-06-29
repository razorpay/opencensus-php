<?php

namespace RZP\Models\Settlement\Processor\Base;

use RZP\Constants\Environment;
use RZP\Exception\ServerErrorException;
use RZP\Mail\Base\Constants;
use RZP\Services\UfhService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Services\Mock\UfhService as MockUfhService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;
use RZP\Trace\TraceCode;

abstract class BaseGifuFile extends Base\Core
{
    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $ufh;

    protected $app;

    protected $transferMode = TransferMode::SFTP;

    const EXTENSION = FileStore\Format::CSV;
    /**
     * @var string
     */
    protected $storageFileName;

    public function __construct()
    {
        parent::__construct();

        $ufhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if ($ufhServiceMock === false)
            $this->ufh = (new UfhService($this->app));
        else
            $this->ufh = (new MockUfhService($this->app));
    }

    /**
     * @throws ServerErrorException
     */
    public function generate($input, $from, $to)
    {
        $gifuData = $this->getGifuData($input,$from,$to);

        if(count($gifuData) === 0)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_FILE_EMPTY_DATA,
                [
                    "description" => "No settlement data found, file generation skipped",
                    "bankName" => $this->bankName
                ]
            );

            return [];
        }

        $fileData = $this->generateGifuFile($gifuData);

        $path  = storage_path('files/filestore').'/'.$fileData['file_name'];

        $file = new UploadedFile($path, $fileData['file_name'],null,null,true);

        $responseFromUfhUpload = $this->uploadFileToUfh($file, $this->type);

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_DATA,
            [
                'UFH Response'   => $responseFromUfhUpload
            ]
        );

        $this->deleteLocalFile($file);

        return $responseFromUfhUpload;
    }

    abstract public function getGifuData($input,$from,$to);

    abstract protected function customFormattingForFile($path,FileStore\Creator $creator = null);

    public function uploadFileToUfh(UploadedFile $file, string $type): array
    {
        $fileName = $this->getFileToWriteName();

        $namespace = explode("\\", strtolower(get_class($this)));
        $subFolder = $namespace[count($namespace) - 2]; // Second last element represents the actual processor

        $this->storageFileName = 'settlements/' . $subFolder . '/' . $fileName;

        $response = $this->ufh->uploadFileAndGetResponse($file, $this->storageFileName, $type, null);

        $this->trace->info(
            TraceCode::UFH_FILE_UPLOAD, [
            'response' => $response,
        ]);

        return [
            'file_id' => $response['id'],
            'success' => isset($response['id']),
            'status' => $response['status'],
            'bucket' => $response['bucket'],
            'region' => $response['region']
        ];
    }

    public function generateGifuFile(array $gifuData, array $metadata = []): array
    {
        $fileName = $this->getFileToWriteName();

        $creator = new FileStore\Creator;

        $creator->extension(static::EXTENSION)
            ->content($gifuData)
            ->name($fileName)
            ->store($this->store)
            ->type($this->type)
            ->metadata($metadata);

        $this->file = $creator->getFileInstance();

        $creator->save();

        $this->customFormattingForFile($creator->getFullFilePath(),$creator);

        $file = $creator->get();

        return [
            'file_name'  => basename($file['local_file_path'])
        ];

    }

    public function sendGifufile()
    {
        if($this->app->environment() === Environment::PRODUCTION)
        {
            $this->pushFileToBeam($this->jobNameProd);
        }
        else
        {
            $this->pushFileToBeam($this->jobNameStage);
        }
    }

    public function pushFileToBeam(string $jobName)
    {
        try
        {
            $fileInfo = [$this->storageFileName];

            $bucketConfig = $this->getBucketConfig();

            $data =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => $jobName,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'settlements',
                'filetype'  => $this->type,
                'subject'   => 'File Send failure',
                'recipient' => $this->mailAddress,
            ];

            $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::BEAM_PUSH_FAILED,
                [
                    'job_name'  => $jobName,
                    'file_name' => $fileInfo,
                ]);
        }
    }

    abstract protected function getBucketConfig();

    protected function getFileToWriteName(): string
    {
        return $this->fileToWriteName;
    }

    protected function deleteLocalFile(UploadedFile $file)
    {
        $name = $this->fileToWriteName;

        $ext = $file->getClientOriginalExtension();

        $filePath = storage_path('files/filestore') . '/' . $name . '.' . $ext;

        if ((file_exists($filePath) === true))
        {
            $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                $this->trace->error(TraceCode::SETTLEMENT_FILE_DELETE_ERROR, ['file_path' => $filePath]);
            }
        }
    }

}
