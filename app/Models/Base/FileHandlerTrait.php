<?php

namespace RZP\Models\Base;

use AWS;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\FileStore\Formatter\ExcelFormatter;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use RZP\Trace\TraceCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileHandlerTrait
{
    protected $saveToAws = true;

    protected $excel = null;

    protected function getFileNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToWriteName. '_' . $mode . '_' . $time;
    }

    protected function getMode()
    {
        $mode = \BasicAuth::getMode();

        return $mode;
    }


    protected function writeToExcelFile($data, $name, $dir = 'files/settlement')
    {
        $columnFormat = [];

        $fileMetadata = ExcelFormatter::writeToExcelFile($data,
                            $name,
                            $columnFormat,
                            'xlsx',
                            $dir);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $fullPath = $fileMetadata['full'];

        $url = $this->saveToAws($name.'.xlsx', $fullPath, $xlsxMimeType);

        return $url;
    }

    protected function saveToAws($name,
                                $fullpath,
                                $mime = 'text/plain',
                                $bucket = 'settlement_bucket',
                                $metadata = array())
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $fullpath;
        }

        $s3 = Handler::getClient();

        try
        {
            $s3Obj = array(
                'Bucket'        => $config[$bucket],
                'Key'           => $name,
                'ContentType'   => $mime,
                'SourceFile'    => $fullpath,
                'Metadata'      => $metadata,
            );

            $this->trace()->info(TraceCode::AWS_FILE_UPLOAD, $s3Obj);

            $result = $s3->putObject($s3Obj);
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        $url = $result['ObjectURL'];

        $this->fileAwsUrl = $url;

        return $url;
    }
}
