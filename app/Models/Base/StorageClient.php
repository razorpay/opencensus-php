<?php

namespace RZP\Models\Base;

use Config;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use \Aws\S3\Exception\S3Exception as AwsException;

class StorageClient
{
    const BASE_FOLDER_PATH = 'default_files_folder/';

    const BUCKET_NAME = 'default_files_bucket';

    /**
     * StorageClient constructor.
     * @param string $folderPath
     * @param string $bucketName
     */
    public function __construct($folderPath = self::BASE_FOLDER_PATH, $bucketName = self::BUCKET_NAME)
    {
        $this->folderPath = $folderPath;

        $this->bucketName = $bucketName;
    }

    public function saveToStorage(array $fileDetails)
    {
        $fileName = $fileDetails['file_name'];

        $awsFileName = $this->folderPath . $fileName;

        $mimeType = $fileDetails['mime_type'];

        $filePath = $fileDetails['file_path'];

        $config = Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock === true)
        {
            $mockFileName = '/' . $awsFileName;

            return $mockFileName;
        }

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOAD,
            $fileDetails);

        $s3 = Handler::getClient();

        try
        {
            $s3Obj = [
                'Bucket'        => $config[$this->bucketName],
                'Key'           => $awsFileName,
                'ContentType'   => $mimeType,
                'SourceFile'    => $filePath,
            ];
            // The method which will upload to s3.
            $result = $s3->putObject($s3Obj);
        }
        catch (AwsException $e)
        {
            throw new Exception\ServerErrorException(
                'Failed to upload file: ' . $awsFileName,
                ErrorCode::SERVER_ERROR_AWS_FAILURE, null, $e);
        }
        catch (\Exception $e)
        {
            throw new Exception\ServerErrorException(
                'Failed to upload file: ' . $awsFileName,
                ErrorCode::SERVER_ERROR_AWS_FAILURE, null, $e);
        }

        $this->trace->info(
            TraceCode::AWS_FILE_UPLOADED,
            $fileDetails);

        $s3Url = $result['ObjectURL'];

        return $s3Url;

    }
}