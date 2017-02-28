<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use Aws;
use Config;

use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base\Handler as BaseHandler;
use RZP\Models\FileStore\Utility;

class Handler extends BaseHandler
{
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('filestore.aws');
    }

    public static function getClient($region = null)
    {
        $awsConfig = Config::get('aws');

        if ($region === null)
        {
            $region = $awsConfig['bucket_region'];
        }

        $awsConfig['region'] = $region;

        $client = new Aws\Sdk($awsConfig);

        return $client->createClient('s3');
    }

    public function save($bucketConfig, $fileDetails)
    {
        if ($this->config['mock'] === true)
        {
            return $fileDetails['path'];
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3SaveObj($bucketConfig['bucket'], $fileDetails);

            $result = $s3->putObject($s3Obj);

            $this->trace->info(TraceCode::AWS_FILE_UPLOAD, $s3Obj);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }

        return $result['ObjectURL'];
    }

    public function saveAs($bucketConfig, $key, $filePath)
    {
        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3FetchObj($bucketConfig['bucket'], $key);

            $s3Obj['SaveAs'] = $filePath;

            $result = $s3->getObject($s3Obj);

            Utility::callFileOperation('chmod', [$filePath, 0777]);

            $this->trace->info(TraceCode::AWS_FILE_DOWNLOAD, $s3Obj);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }
    }

    public function getSignedUrl($bucketConfig, $key, $duration = '15')
    {
        if ($this->config['mock'] === true)
        {
            return $key;
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3FetchObj($bucketConfig['bucket'], $key);

            $command = $s3->getCommand('GetObject', $s3Obj);

            $request = $s3->createPresignedRequest(
                $command,
                '+' . $duration . ' minutes'
            );

            $preSignedUrl = (string) $request->getUri();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }

        return $preSignedUrl;
    }

    protected function getS3SaveObj($bucket, $fileDetails)
    {
        $s3Obj = $this->getS3FetchObj($bucket, $fileDetails['key']);

        $s3ContentObj = [
            'ContentType' => $fileDetails['mime'],
            'SourceFile'  => $fileDetails['path'],
            'Metadata'    => $fileDetails['metadata'],
        ];

        $s3Obj = array_merge($s3Obj, $s3ContentObj);

        return $s3Obj;
    }

    protected function getS3FetchObj($bucket, $key)
    {
        $s3Obj = [
            'Bucket' => $bucket,
            'Key'    => $key
        ];

        return $s3Obj;
    }
}
