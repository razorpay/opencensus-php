<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use Aws;
use Config;

use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base\Handler as BaseHandler;
use RZP\Models\FileStore\Utility;

class Handler extends BaseHandler
{
    /**
     * Config for the Handler Instance
     */
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('filestore.aws');
    }

    /**
     * Static function to get the Aws Client
     *
     * @param string|null $region region of s3 bucket
     *
     * @return Aws\Sdk Aws S3 client
     */
    public static function getClient($region = null)
    {
        $awsConfig = Config::get('aws');

        $awsConfig['region'] = $region ?: $awsConfig['bucket_region'];

        $client = new Aws\Sdk($awsConfig);

        return $client->createClient('s3');
    }

    /**
     * Saves the File in Aws and returns Url of file saved
     *
     * @param array $bucketConfig Bucket config having name and region
     * @param array $fileDetails  Array containing File Params
     *
     * @return string saved file Url
     */
    public function save(array $bucketConfig, array $fileDetails)
    {
        if ($this->config['mock'] === true)
        {
            return $fileDetails['path'];
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3SaveObj($bucketConfig['name'], $fileDetails);

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

    /**
     * Download File from AWS and stores in the $filePath provided
     *
     * @param array  $bucketConfig bucket config
     * @param string $key          Key of file to be saved
     * @param string $filePath     File path where files should be saved
     *
     * @return void
     * @throws \Exception
     */
    public function saveAs($bucketConfig, $key, $filePath)
    {
        if ($this->config['mock'] === true)
        {
            return $filePath;
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3FetchObj($bucketConfig['name'], $key);

            $s3Obj['SaveAs'] = $filePath;

            $result = $s3->getObject($s3Obj);

            //
            // Need to change permission of downloaded file,
            // as other user may need to override this file
            //
            if (substr(sprintf('%o', fileperms($filePath)), -3) !== '777')
            {
                (new Utility)->callFileOperation('chmod', [$filePath, 0777]);
            }

            $this->trace->info(TraceCode::AWS_FILE_DOWNLOAD, $s3Obj);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }
    }

    /**
     * Get Signed Url for Given Key
     *
     * @param array  $bucketConfig bucket config
     * @param string $key          File for which the signed url should be fetched
     * @param string $duration     Validity of signed url
     * @param array  $params       Additional parameters, if any
     *
     * @return string Signed url
     * @throws \Exception
     */
    public function getSignedUrl(
        array $bucketConfig,
        string $key,
        string $duration = '15',
        array $params = [])
    {
        if ($this->config['mock'] === true)
        {
            return $key;
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            $s3Obj = $this->getS3FetchObj($bucketConfig['name'], $key, $params);

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

    /**
     * Get Url for Given Key in a Bucket
     *
     * @param array  $bucketConfig bucket config
     * @param string $key          File for which the signed url should be fetched
     *
     * @return string Url
     * @throws \Exception
     */
    public function getUrl($bucketConfig, $key)
    {
        if ($this->config['mock'] === true)
        {
            return $key;
        }

        $s3 = self::getClient($bucketConfig['region']);

        try
        {
            return $s3->getObjectUrl($bucketConfig['name'], $key);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }
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

    protected function getS3FetchObj(
        string $bucket,
        string $key,
        array $params = [])
    {
        $s3Obj = [
            'Bucket' => $bucket,
            'Key'    => $key,
        ];

        $downloadAs = $params['downloadAs'] ?? null;

        if ($downloadAs !== null)
        {
            $s3Obj += [
                'ResponseContentDisposition' => "attachment; filename=\"$downloadAs\"",
            ];
        }

        return $s3Obj;
    }
}
