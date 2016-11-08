<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use AWS;
use Config;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base;

class Handler extends Base\Handler
{
    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('aws');
    }

    public function save($bucket, $name, $fullpath, $mime, $metadata = [])
    {
        if ($this->config['mock'] === true)
        {
            return $fullpath;
        }

        $s3 = $this->getClient();

        try
        {
            $s3Obj = $this->getS3SaveObj($bucket, $name, $fullpath, $mime, $metadata);

            $result = $s3->putObject($s3Obj);

            $this->trace()->info(TraceCode::AWS_FILE_UPLOAD, $s3Obj);
        }
        catch(\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        return $result['ObjectURL'];
    }

    public function fetchAndSaveFile($bucket, $name, $filePath)
    {
        if ($this->config['mock'] === true)
        {
            return $filePath;
        }

        $s3 = $this->getClient();

        try
        {
            $s3Obj = $this->getS3FetchObj($bucket, $name);

            $s3Obj['SaveAs'] = $filePath;

            $result = $s3->getObject($s3Obj);

            $this->trace()->info(TraceCode::AWS_FILE_DOWNLOAD, $s3Obj);
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        return $filePath;
    }

    public function fetchContent($bucket, $name)
    {
        if ($this->config['mock'] === true)
        {
            return '';
        }

        $s3 = $this->getClient();

        try
        {
            $s3Obj = $this->getS3FetchObj($bucket, $name);

            $result = $s3->getObject($s3Obj);

            $this->trace()->info(TraceCode::AWS_FILE_DOWNLOAD, $s3Obj);
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        return $result['Body'];
    }

    public function delete()
    {
        $s3 = $this->getClient();

        try
        {
            $s3Obj = $this->getS3FetchObj($bucket, $key);

            $result = $s3->deleteObject($s3Obj);

            $this->trace()->info(TraceCode::AWS_FILE_DELETE, $s3Obj);
        }
        catch(\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        return $result['DeleteMarker'];
    }

    public function getTemporaryUrl($bucket, $key, $duration = '15')
    {
        if ($this->config['mock'] === true)
        {
            return $key;
        }

        $s3 = $this->getClient();

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

    public function getBucketName($entityName)
    {
        if ($this->getMode() === 'test')
        {
            return 'rzp-test-bucket';
        }

        $bucketType = Bucket::BUCKET_MAP[$this->file->type];

        return $this->config[$bucketType];
    }

    protected function getClient()
    {
        return AWS::createClient('s3');
    }

    protected function getS3SaveObj($bucket, $name, $fullpath, $mime, $metadata)
    {
        $s3Obj = $this->getS3FetchObj($bucket, $name);

        $s3ContentObj = [
            'ContentType' => $mime,
            'SourceFile'  => $fullpath,
            'Metadata'    => $metadata,
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

    protected function getMode()
    {
        return \BasicAuth::getMode();
    }
}
?>
