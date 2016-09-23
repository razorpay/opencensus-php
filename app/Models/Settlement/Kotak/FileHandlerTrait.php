<?php

namespace RZP\Models\Settlement\Kotak;

use AWS;
use Excel;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileHandlerTrait
{
    protected $saveToAws = true;

    protected $excel = null;

    private $_zipCommand = "zip --junk-paths --move";

    public function writeToTextFile($txt)
    {
        $name = $this->getFileToWriteName();

        $fullpath = $this->saveLocally($name, $txt);

        $url = $this->saveToAws($name, $fullpath, 'text/plain');

        // This will be local file path if aws is mocked
        return $url;
    }

    public function writeToTextFileH2H($txt)
    {
        try
        {
            $name = 'RAZORNODAL\$\$'. Carbon::now('Asia/Kolkata')->format('dmYHis') . '.txt';

            $fullpath = $this->saveLocally($name, $txt);

            $bucket = 'h2h_bucket';

            $metadata = $this->getH2HMetadata();

            $key = 'kotak/outgoing/' . $name;

            $url = $this->saveToAws($key, $fullpath, 'text/plain', $bucket, $metadata);

            // This will be local file path if aws is mocked
            return $url;
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException($e);
        }
    }

    public function writeToCsvFile($data, $name, $fullName = null)
    {
        $excelObject = $this->createExcelObject($data, $name);

        $fileMetadata = $excelObject->store('csv', storage_path('files/settlement'), true);
        $fullpath = $fileMetadata['full'];

        if ($fullName != null)
        {
            rename($fullpath, $fullName);
            $fullpath = $fullName;
        }

        $url = $this->saveToAws($name, $fullpath, 'text/csv');

        // This will be local file path if aws is mocked
        return $url;
    }

    public function writeToExcelFile($data, $name)
    {
        \Config::set('excel::export.calculate', true);

        $columnFormat = $this->getColumnFormatForExcel();

        $excel = $this->createExcelObject($data, $name, $columnFormat);

        $fileMetadata = $excel->store('xlsx', storage_path('files/settlement'), true);
        $fullpath = $fileMetadata['full'];

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType);

        return $url;
    }


    public function writeToExcelFileH2H($data, $name)
    {
        \Config::set('excel::export.calculate', true);

        $columnFormat = $this->getColumnFormatForExcel();

        $excel = $this->createExcelObject($data, $name, $columnFormat);

        $fileMetadata = $excel->store('xlsx', storage_path('files/settlement'), true);
        $fullpath = $fileMetadata['full'];

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $bucket = 'h2h_bucket';

        $metadata = $this->getH2HMetadata();

        $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType, $bucket, $metadata);

        return $url;
    }

    public function getH2HFileFromAws($key)
    {
        $bucket = 'h2h_bucket';

        $name = $this->getFileToWriteName();

        $fullPath = $this->getFullFilePath($name);

        return $this->getFileFromAws($bucket, $key, $fullPath);
    }

    protected function createExcelObject($data, $name, $columnFormat = [])
    {
        $excel = Excel::create($name, function($excel) use ($data, $columnFormat)
        {
            $excel->sheet('Sheet 1', function($sheet) use ($data, $columnFormat)
                {
                    // If a columnFormat variable is specified.
                    // Use it.
                    if (empty($columnFormat) === false)
                    {
                        $sheet->setColumnFormat($columnFormat);
                    }

                    $sheet->fromArray($data, null, 'A1', true, true);
                });
        });

        $excel->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(14);

        $this->excel = $excel;

        return $excel;
    }

    protected function getColumnFormatForExcel()
    {
        $columnFormat = [];

        if (isset(self::$format))
        {
            foreach (self::$format as $heading => $type)
            {
                $columnIndex = $this->getColumnIndexForHeading($heading);

                $columnType = $this->getColumnType($type);

                $columnFormat[$columnIndex] = $columnType;
            }
        }

        return $columnFormat;
    }

    protected function getColumnType($type)
    {
        switch ($type) {
            case 'text':
                return '@';
                break;

            default:
                break;
        }

        return null;
    }

    /**
     * Function to get Column Index For a given heading in an Excel.
     *
     * @param string $heading Heading in Excel
     * @return string Column Index
     */
    protected function getColumnIndexForHeading($heading)
    {
        $columnIndex = null;

        $headings = self::$headings;

        $headingsToColumnIdMap = array_flip($headings);

        if (isset($headingsToColumnIdMap[$heading]))
        {
            // logic to get A-Z from number
            $columnIndex = $this->getColumnIndexFromNumber($headingsToColumnIdMap[$heading]);
        }

        return $columnIndex;
    }


    /**
     * Function to convert get an Excel Column Index from a column number.
     *
     * Ref: http://stackoverflow.com/questions/7664121/php-converting-number-to-alphabet-and-vice-versa
     * @param int $data Column number in excel
     * @return string ColumnIndex
     */
    protected function getColumnIndexFromNumber($data)
    {
        $alphabet = range('A','Z');

        $alpha_flip = array_flip($alphabet);

        if($data <= 25)
        {
          return $alphabet[$data];
        }
        elseif($data > 25)
        {
          $dividend = ($data + 1);

          $alpha = '';

          $modulo;

          while ($dividend > 0)
          {
            $modulo = ($dividend - 1) % 26;

            $alpha = $alphabet[$modulo] . $alpha;

            $dividend = floor((($dividend - $modulo) / 26));
          }

          return $alpha;
        }
    }

    protected function saveUploadedFileToAws($fullpath)
    {
        $name = $this->getFileToReadName();

        return $this->saveToAws($name, $fullpath, 'text/plain');
    }

    protected function saveToAws(
        $name, $fullpath, $mime = 'text/plain', $bucket = 'settlement_bucket', $metadata = array())
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $fullpath;
        }

        $s3 = AWS::createClient('s3');

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

    protected function getFileFromAws($bucket, $key, $filePath)
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $filePath;
        }

        $s3 = AWS::createClient('s3');

        try
        {
            $request = array(
                'Bucket'    => $config[$bucket],
                'Key'       => $key,
                'SaveAs'    => $filePath
            );

            $result = $s3->getObject($request);

            $this->trace()->info(TraceCode::AWS_FILE_DOWNLOAD, $request);
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException($e);

            throw $e;
        }

        return $filePath;
    }

    protected function getPreSignedUrlFromAws($bucket, $key, $filePath)
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $filePath;
        }

        $s3 = AWS::createClient('s3');

        $awsBucket = $config[$bucket];

        $url = $s3->getObjectUrl($awsBucket, $key,
                '+10 minutes', [
                    'https'     => true
            ]);

        return $url;
    }

    protected function saveLocally($name, $txt)
    {
        $fullpath = $this->getFullFilePath($name);

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);

        chmod($fullpath, 0777);  // keep it 0777. This step is important.

        return $fullpath;
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row)) ;

            $count--;

           if (($ignoreLastNewline === false) or
               (($ignoreLastNewline === true) and ($count > 0)))
           {
                $txt .= "\r\n";
           }
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

    protected function getExcelFileToReadName()
    {
        return $this->getFileToReadNameWithoutExt().'.xlsx';
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

    protected function getCsvFileToWriteName()
    {
        return $this->getFileToWriteNameWithoutExt() . '.csv';
    }

    protected function getCsvFullFilePath()
    {
        $name = $this->getCsvFileToWriteName();

        return $this->getFullFilePath($name);
    }

    protected function getExcelFullFilePath()
    {
        $name = $this->getExcelFileToWriteName();

        return $this->getFullFilePath($name);
    }

    protected function getTextFullFilePath()
    {
        $name = $this->getFileToWriteName();

        return $this->getFullFilePath($name);
    }

    protected function getZipFileToWriteName()
    {
        return $this->getFileToWriteNameWithoutExt() . '.zip';
    }

    protected function getZipFullFilePath()
    {
        $name = $this->getZipFileToWriteName();

        return $this->getFullFilePath($name);
    }

    protected function makeZipFile($fileArray, $password = null)
    {
        $zipPath = $this->getZipFullFilePath();

        foreach ($fileArray as $file)
        {
            $this->addFileToZip($file, $zipPath, $password);
        }

        return $zipPath;
    }

    private function addFileToZip($filePath, $zipPath, $password)
    {
        $zipCommand = $this->_zipCommand;

        if (isset($password))
        {
            $zipCommand .= " --password " . $password;
        }

        exec($zipCommand . " " . escapeshellarg($zipPath) . " " . escapeshellarg($filePath));
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

        foreach ($rows as $ix => $row)
        {
            // Ending row may be just empty.
            if ($row === '')
            {
                continue;
            }

            $values = explode('~', $row);

            if (count($headings) !== count($values))
            {
                throw new Exception\RuntimeException(
                    'Count of array elements for combine not equal. Heading count: ' .
                    count($headings). ' Value count: ' . count($values) . ' Row: ' . $ix);
            }

            $values = array_combine($headings, $values);
            $data[] = $values;
        }

        return $data;
    }

    protected function parseExcelFile($filePath)
    {
        $data = Excel::load($filePath)
                      ->formatDates(false)
                      ->toArray();
        return $data;
    }

    protected function getFileLines($file)
    {
        $filePath = $file;

        if ($file instanceof UploadedFile)
        {
            $filePath = $file->getRealPath();
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

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

    protected function getFullFilePath($filename)
    {
        return $this->getStorageDir() . '/' . $filename;
    }

    protected function trace()
    {
        $trace = \Trace::getFacadeRoot();

        return $trace;
    }

    protected function getH2HMetadata()
    {
        return array(
            'gid'   => '10000',
            'uid'   => '10001',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        );
    }
}
