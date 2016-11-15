<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use AWS;
use Config;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base;

class Handler extends Base\Handler
{
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('aws');
    }

    public static function getClient()
    {
        $awsConfig = Config::get('aws');

        $awsConfig['region'] = $awsConfig['bucket_region'];

        $client = new \Aws\Sdk($awsConfig);

        return $client->createClient('s3');
    }

    public function save($bucket, $fileDetails)
    {
        if ($this->config['mock'] === true)
        {
            return $fileDetails['path'];
        }

        $s3 = self::getClient();

        try
        {
            $s3Obj = $this->getS3SaveObj($bucket, $fileDetails);

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

    public function getSignedUrl($bucket, $key, $duration = '15')
    {
        if ($this->config['mock'] === true)
        {
            return $key;
        }

        $s3 = self::getClient();

        try
        {
            $s3Obj = $this->getS3FetchObj($bucket, $key);

            $command = $s3->getCommand('GetObject', $s3Obj);

            $request = $s3->createPresignedRequest(
                $command,
                '+' . $duration . ' minutes'
            );

            $presignedUrl = (string) $request->getUri();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }

        return $presignedUrl;
    }

    public function getBucketName($type)
    {
        if ($this->mode === Mode::TEST)
        {
            return 'rzp-test-bucket';
        }

        $bucketType = Bucket::BUCKET_MAP[$type];

        return $this->config[$bucketType];
    }

    protected function getS3SaveObj($bucket, $fileDetails)
    {
        $s3Obj = $this->getS3FetchObj($bucket, $fileDetails['name']);

        $s3ContentObj = [
            'ContentType' => $fileDetails['extension'],
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
