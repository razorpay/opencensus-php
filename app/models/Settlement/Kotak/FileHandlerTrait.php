<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileHandlerTrait
{
    protected $saveToAws = true;

    public function writeToTextFile($txt)
    {
        $name = $this->getFileToWriteName();

        $fullpath = $this->saveLocally($name, $txt);

        $url = $this->saveToAws($name, $fullpath, 'text/plain');

        // This will be local file path if aws is mocked
        return $url;
    }

    public function writeToExcelFile($data, $name)
    {
        \Config::set('excel::export.calculate', true);

        $excel = $this->createExcelObject($data, $name);

        $fileMetadata = $excel->store('xlsx', storage_path('files/settlement'), true);
        $fullpath = $fileMetadata['full'];

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType);

        return $url;
    }

    protected function createExcelObject($data, $name)
    {
        $excel = Excel::create($name, function($excel) use ($data)
        {
            $excel->sheet('Sheet 1', function($sheet) use ($data)
                {
                    $sheet->fromArray($data, null, 'A1', true, true);
                });
        });

        $excel->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(14);

        return $excel;
    }

    protected function saveUploadedFileToAws($fullpath)
    {
        $name = $this->getFileToReadName();

        return $this->saveToAws($name, $fullpath, 'text/plain');
    }

    protected function saveToAws($name, $fullpath, $mime = 'text/plain')
    {
        $config =  \Config::get('aws::config');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $fullpath;
        }

        $s3 = \App::make('aws')->get('s3');

        try
        {
            $s3Obj = array(
                'Bucket'        => $config['settlement_bucket'],
                'Key'           => $name,
                'ContentType'   => $mime,
                'SourceFile'    => $fullpath,
            );

            $result = $s3->putObject($s3Obj);
        }
        catch(\Exception $e)
        {
            // trace here.
            throw $e;
        }

        $url = $result['ObjectURL'];

        $this->fileAwsUrl = $url;

        return $url;
    }

    protected function saveLocally($name, $txt)
    {
        $path = $this->getStorageDir();

        $fullpath = $path . $name;

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);
        chmod($fullpath, 0777);  // keep it 0777. This step is important.

        return $fullpath;
    }

    protected function generateText($data)
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= implode('~', array_values($row)) . "\r\n";
        }

        return $txt;
    }

    protected function getFile($input)
    {
        if (isset($input['file']))
        {
            return $this->moveFile($input['file']);
        }

        return $this->getFileIfExists();
    }

    protected function getFileIfExists()
    {
        $name = $this->getFileToReadName();

        $path = storage_path('files/settlement');

        $fullpath = $path . '/' . $name;

        if (file_exists($fullpath) === false)
        {
            // @todo: trace here
            return null;
        }

        return $fullpath;
    }

    public function deleteFileIfExists()
    {
        $fullPath = $this->getFileIfExists();

        if ($fullPath !== null)
        {
            $success = unlink($fullPath);

            if ($success === false)
            {
                throw new Exception\RuntimeErrorException(
                    'Failed to delete file: ' . $fullPath);
            }
        }
    }

    protected function getFileToReadName()
    {
        return $this->getFileToReadNameWithoutExt().'.txt';
    }

    protected function getFileToReadNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToReadName.'_'.$mode.'_'.$time;
    }

    protected function getFileToReadFullPath()
    {
        $name = $this->getFileToReadName();

        return $this->getStoragePath($name);
    }

    protected function getStoragePath($path = '')
    {
        $folder = 'files/settlement';

        $path = $folder . ($path ? '/'.$path : $path);

        return storage_path($path);
    }

    protected function getFileToWriteName()
    {
        return $this->getFileToWriteNameWithoutExt() . '.txt';
    }

    protected function getExcelFileToWriteName()
    {
        return $this->getFileToWriteNameWithoutExt() . '.xlsx';
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToWriteName.'_'.$mode.'_'.$time;
    }

    protected function parseTextFile($file)
    {
        $rows = $this->getFileLines($file);

        $data = array();
        $headings = $this->getHeadings();

        foreach ($rows as $row)
        {
            // Ending row may be just empty.
            if ($row === '')
                continue;

            $values = explode('~', $row);
            $values = array_combine($headings, $values);
            $data[] = $values;
        }

        return $data;
    }

    protected function getFileLines($file)
    {
        $filePath = $file;

        if ($file instanceof UploadedFile)
        {
            $filePath = $file->getRealPath();
        }

        $file = fopen($filePath, 'r');
        $txt = fread($file, filesize($filePath));
        $lines = explode("\r\n", $txt);

        return $lines;
    }

    public static function getHeadings()
    {
        return static::$headings;
    }

    protected function storeReconciledFile($file)
    {
        $filename = basename($file, '.txt');

        $dir = storage_path('files/settlement/reconciled');

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $time = Carbon::now('Asia/Kolkata')->format('H:i:s');

        $mode = $this->getMode();

        $newName = $dir . '/' . $filename . '_' . $mode.'_'.$time . '.txt';

        $res = rename($file, $newName);

        if ($res === false)
        {
            throw new Exception\RuntimeErrorException(
                'Failed to rename file. File : ' . $file .
                ' Renamed name: ' . $newFilepath);
        }

        return $newName;
    }

    protected function moveFile($file)
    {
        $uploadedFilePath = $file->getRealPath();

        $newFilepath = $this->getFileToReadFullPath();

        $dir = $this->getStorageDir();

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $res = rename($uploadedFilePath, $newFilepath);

        if ($res === false)
        {
            throw new Exception\RuntimeErrorException(
                'Failed to rename file. Uploaded name: ' . $uploadedFilePath .
                ' Renamed name: ' . $newFilepath);
        }

        return $newFilepath;
    }

    protected function getMode()
    {
        $mode = \BasicAuth::getMode();

        return $mode;
    }

    protected function getStorageDir()
    {
        return storage_path('files/settlement');
    }
}