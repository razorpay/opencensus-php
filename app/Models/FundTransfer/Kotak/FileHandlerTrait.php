<?php

namespace RZP\Models\FundTransfer\Kotak;

use AWS;
use App;
use Excel;
use Config;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use Razorpay\Trace\Logger as Trace;
use RZP\Excel\Import as ExcelImport;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use RZP\Excel\MultipleSheetsImport as ExcelMultipleSheetsImport;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;

trait FileHandlerTrait
{
    protected $saveToAws = true;

    protected $excel = null;

    private $_zipCommand = 'zip --junk-paths --move';

    public function writeToTextFile($name, $txt, $mime = 'text/plain')
    {
        $fullpath = $this->createTxtFile($name, $txt);

        $url = $this->saveToAws($name, $fullpath, $mime);

        // This will be local file path if aws is mocked
        return $url;
    }

    public function writeToTextFileH2H($name, $txt)
    {
        try
        {
            $fullpath = $this->createTxtFile($name, $txt);

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

    public function writeToCsvFile($data, $name, $fullName = null, $dir = 'files/settlement')
    {
        $fullpath = $this->createCsvFile($data, $name, $fullName, $dir);

        $url = $this->saveToAws($name, $fullpath, 'text/csv');

        // This will be local file path if aws is mocked
        return $url;
    }

    public function writeToExcelFile($data, $name, $dir = 'files/settlement', $sheetNames = ['Sheet 1'], $extension = 'xlsx')
    {
        $fullpath = $this->createExcelFile($data, $name, $dir, $sheetNames, $extension);

        $xlsxMimeType = (($extension === 'xls') ? 'application/vnd.ms-office'
                                             : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $url = $this->saveToAws($name . '.' . $extension, $fullpath, $xlsxMimeType);

        return $url;
    }

    public function writeToExcelFileH2H($data, $name, $dir = 'files/settlement')
    {
        $fullpath = $this->createExcelFile($data, $name, $dir);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $bucket = 'h2h_bucket';

        $metadata = $this->getH2HMetadata();

        $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType, $bucket, $metadata);

        return $url;
    }

    public function createExcelFile($data, $name, $directory, $sheetNames = ['Sheet 1'], $extension = 'xlsx')
    {
        $columnFormat = $this->getColumnFormatForExcel();

        $directory = storage_path($directory);

        $fileMetadata = $this->createExcelObject($data, $directory, $name, $extension, $columnFormat, $sheetNames);

        // $fileMetadata = $excel->store($extension, storage_path($dir), true);

        $fullpath = $fileMetadata['full'];

        return $fullpath;
    }

    public function createCsvFile($data, $name, $fullName, $dir, $append = false)
    {
        $dir = storage_path($dir);

        if (file_exists($dir) === false)
        {
            mkdir($dir);
        }

        $fullpath = $dir . '/' . $name . '.csv';

        // open the file in append mode
        $handle = fopen($fullpath, 'a');

        $first = true;

        foreach ($data as $row)
        {
            if (($append === false) and ($first === true))
            {
                $headers = array_keys($row);

                fputcsv($handle, $headers);

                $first = false;
            }

            $row = $this->flatten($row);

            fputcsv($handle, $row);
        }

        fclose($handle);

        if ($fullName !== null)
        {
            rename($fullpath, $fullName);

            $fullpath = $fullName;
        }

        return $fullpath;
    }

    protected function getFileExtension(string $key): string
    {
        $extension = pathinfo($key, PATHINFO_EXTENSION);

        if (empty($extension) === false)
        {
            $extension = '.' . $extension;
        }
        else
        {
            $extension = '.txt';
        }

        return $extension;
    }

    public function getH2HFileFromAws($key, $useKeyForFileName = false, $bucket = 'h2h_bucket', $region = null, bool $configKey = false)
    {
        if ($useKeyForFileName === false)
        {
            $extension = $this->getFileExtension($key);

            $name = $this->getFileToWriteName($extension);

            $fullPath = $this->getFullFilePath($name);
        }
        else
        {
            $fullPath = $this->getFullFilePath($key);

            $dir = dirname($fullPath);

            if (file_exists($dir) === false)
            {
                (new FileStore\Utility)->callFileOperation('mkdir', [$dir, 0777, true]);
            }
        }

        return $this->getFileFromAws($key, $fullPath, $bucket, $region, $configKey);
    }

    public function deleteFileIfExists()
    {
        $fullPath = $this->getFileIfExists();

        if ($fullPath !== null)
        {
            $success = unlink($fullPath); // nosemgrep : php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                throw new Exception\RuntimeException(
                    'Failed to delete file: ' . $fullPath);
            }
        }
    }

    public function getFileBasename($fileFullPath)
    {
        return basename($fileFullPath);
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     *
     * @param array $row
     *
     * @return array flat version of input array
     */
    protected function flatten(array $row)
    {
        foreach ($row as &$value)
        {
            if (is_array($value))
            {
                $value = json_encode($value);
            }
        }

        return $row;
    }

    protected function createExcelObject($data, $dir, $filename, $extension, $columnFormat = [], $sheetNames = ['Sheet 1'])
    {
        $path = $dir . DIRECTORY_SEPARATOR . $filename . '.' . $extension;

        (new ExcelExport)->setSheets(function() use ($data, $sheetNames, $columnFormat) {
                $sheetsInfo = [];
                foreach ((array) $sheetNames as $sheetName)
                {
                    $sheetsInfo[$sheetName] = (new ExcelSheetExport($data[$sheetName] ?? $data))
                                                    ->setTitle($sheetName)
                                                    ->setColumnFormat($columnFormat)
                                                    ->generateAutoHeading(true)
                                                    ->setStyle(function($sheet) {
                                                        $sheet->getParent()
                                                             ->getDefaultStyle()
                                                             ->getFont()
                                                             ->setName('Ubuntu Mono')
                                                             ->setSize(14);
                                                    });
                }

                return $sheetsInfo;
            })->store($path, 'local_storage');

        return [
            'full'  => $path,
            'path'  => $dir,
            'file'  => $filename . '.' . $extension,
            'title' => $filename,
            'ext'   => $extension
        ];
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

        if ($data <= 25)
        {
          return $alphabet[$data];
        }
        else if ($data > 25)
        {
          $dividend = ($data + 1);

          $alpha = '';

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
        $name, $fullPath, $mime = 'text/plain', $bucket = 'settlement_bucket', $metadata = [])
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $fullPath;
        }

        $s3 = Handler::getClient();

        try
        {
            $s3Obj = [
                'Bucket'        => $config[$bucket],
                'Key'           => $name,
                'ContentType'   => $mime,
                'SourceFile'    => $fullPath,
                'Metadata'      => $metadata,
            ];

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

    protected function getFileFromAws(
        string $key,
        string $filePath,
        string $bucketConfigKey = 'settlement_bucket',
        string $region = null,
        bool $configKey = false)
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $key;
        }

        $s3 = Handler::getClient($region);

        if($configKey)
        {
            $bucket = $bucketConfigKey;
        }
        else{
            $bucket = $config[$bucketConfigKey];
        }

        $request = [
            'Bucket'    => $bucket,
            'Key'       => $key,
            'SaveAs'    => $filePath
        ];

        try
        {
            $result = $s3->getObject($request);

            $this->trace()->info(TraceCode::AWS_FILE_DOWNLOAD, $request);
        }
        catch (\Throwable $e)
        {
            $this->trace()->traceException(
                                $e,
                                null,
                                TraceCode::AWS_FILE_DOWNLOAD_ERROR,
                                $request);

            throw $e;
        }

        return $filePath;
    }

    protected function deleteFileFromAws(string $key, string $bucketConfigKey, string $region = null): bool
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return true;
        }

        $s3 = Handler::getClient($region);

        $request = [
            'Bucket'    => $config[$bucketConfigKey],
            'Key'       => $key,
        ];

        try
        {
            $result = $s3->deleteObject($request);

            $this->trace()->info(TraceCode::AWS_FILE_DELETE, $request);
        }
        catch (\Throwable $e)
        {
            $this->trace()->traceException($e, null, TraceCode::AWS_FILE_DELETE_ERROR, $request);

            throw $e;
        }

        return true;
    }

    protected function getPreSignedUrlFromAws($key, $bucket = 'settlement_bucket', $ttl = '+10 minutes')
    {
        $config =  \Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock)
        {
            return $key;
        }

        $s3 = Handler::getClient();

        $awsBucket = $config[$bucket];

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $awsBucket,
            'Key'    => $key
        ]);

        $request = $s3->createPresignedRequest($cmd, $ttl);

        // Get the actual presigned-url
        $presignedUrl = (string) $request->getUri();

        return $presignedUrl;
    }

    public function createTxtFile(string $name, string $txt, string $dir = null)
    {
        //
        // If directory is not provided(default case) usage /settlement else
        // the one provided.
        //
        $fullpath = ($dir === null) ? $this->getFullFilePath($name) : "{$dir}/{$name}";

        $dir = dirname($fullpath);

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777, true);
        }

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);

        try
        {
            chmod($fullpath, 0777);  // keep it 0777. This step is important
        }
        catch (\Exception $e)
        {
            $this->trace()->traceException(
                $e,
                Trace::WARNING,
                TraceCode::FILE_PERMISSION_CHANGE_FAILED,
                [
                    'path' => $fullpath
                ]);
        }

        return $fullpath;
    }

    protected function generateTextWithHeadings($data, $glue = '~', $ignoreLastNewline = false, array $headings = [])
    {
        array_unshift($data, array_combine($headings, $headings));

        return $this->generateText($data, $glue, $ignoreLastNewline);
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            if ((array_key_exists(Header::ERROR_DESCRIPTION, $row) !== false) and
                (strpos($row[Header::ERROR_DESCRIPTION], ',') !== false))
            {
                $row[Header::ERROR_DESCRIPTION] = "\"" . $row[Header::ERROR_DESCRIPTION] . "\"";
            }

            $txt .= implode($glue, array_values($row));

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
        if (isset($input['file']) === true)
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

    protected function getFileToReadName(string $extension = 'txt')
    {
        return $this->getFileToReadNameWithoutExt() . '.' . $extension;
    }

    protected function getExcelFileToReadName()
    {
        return $this->getFileToReadNameWithoutExt().'.xlsx';
    }

    protected function getFileToReadNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToReadName.'_'.$mode.'_'.$time;
    }

    protected function getFileToReadFullPath(string $extension = 'txt')
    {
        $name = $this->getFileToReadName($extension);

        return $this->getStoragePath($name);
    }

    protected function getStoragePath($path = '')
    {
        $folder = 'files/settlement';

        $path = $folder . ($path ? '/'.$path : $path);

        return storage_path($path);
    }

    protected function getFileToWriteName($ext = '.txt')
    {
        return $this->getFileToWriteNameWithoutExt() . $ext;
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
            $zipCommand .= ' --password ' . $password;
        }

        exec(escapeshellcmd($zipCommand) . ' ' . escapeshellarg($zipPath) . ' ' . escapeshellarg($filePath)); // nosemgrep : php.lang.security.exec-use.exec-use
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToWriteName.'_'.$mode.'_'.$time;
    }

    protected function parseTextFile(string $file, string $delimiter = '~')
    {
        $rows = $this->getFileLines($file);

        $data = [];

        $headings = $this->parseFirstRowAndGetHeadings($rows, $delimiter);

        foreach ($rows as $ix => $row)
        {
            // Ending row may be just empty.
            if (blank($row) === false)
            {
                $data[] = $this->parseTextRow($row, $ix, $delimiter, $headings);
            }
        }

        return $data;
    }

    protected function parseXmlFile(string $file)
    {
        $filePath = $file;

        if ($file instanceof UploadedFile)
        {
            $filePath = $file->getRealPath();
        }

        $formattedXml = simplexml_load_file($filePath);

        return json_decode(json_encode($formattedXml), true);
    }

    protected function parseCsvFile(string $file, string $delimiter = ',')
    {
        $rows = $this->getFileLines($file);

        $data = [];

        foreach ($rows as $ix => $row)
        {
            if (blank($row) === false)
            {
                $data[] = str_getcsv($row, $delimiter);
            }
        }

        return $data;
    }

    /**
     * Reads first row and if it's the header row, pulls it from rows and usage this as heading for doing array_combine
     * in further flows (e.g. parseTextRow).
     * @return array|null
     */
    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
    }

    protected function parseTextRow(string $row, int $ix, string $delimiter, array $headings = null)
    {
        $headings = $headings ?: $this->getHeadings();

        $values = str_getcsv($row, $delimiter);

        if (count($headings) !== count($values))
        {
            $values = $this->parseTextRowWithHeadingMismatch($headings, $values, $ix);
        }
        else
        {
            $values = array_map(
                function($value) use ($delimiter) {
                    if ((empty($value) === false) and (str_contains($value, $delimiter) === true))
                    {
                        return '"' . $value . '"';
                    }
                    return $value;
                },
                $values
            );

            $values = array_combine($headings, $values);
        }

        return $values;
    }

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix)
    {
        throw new Exception\LogicException(
            'Count of array elements for combine not equal. Heading count: ' .
            count($headings). ' Value count: ' . count($values) . ' Row',
            null,
            [
                'line'      => $ix,
                'content'   => $values
            ]);
    }

    protected function parseExcelFile(string $filePath, array $sheetNames = [], $startRow = 1, $heading = 'slug')
    {
        // In excel 2.1, startRow is actually the heading Row. To maintain the same behaviour
        // in excel 3.1, we will set startRow as $startRow + 1 and heading Row  s $startRow
        $import = new ExcelImport($startRow);

        if (empty($sheetNames) === false)
        {
            $import = (new ExcelMultipleSheetsImport($startRow))->setSheets($sheetNames);
        }

        return $import->setHeadingType($heading)->toArray($filePath);
    }

    protected function parseExcelSheets($filePath, $startRow = 1)
    {
        // $app = App::getFacadeRoot();

        // Config::set('excel.import.force_sheets_collection', true);
        // Config::set('excel.import.heading', 'original');
        // Config::set('excel.import.startRow', $startRow);

        // //
        // // Calling LaravelExcelReader's setSelectedSheets() and setSelectedSheetIndices() to
        // // reset selected sheet names and indices here, as its not happening in LaravelExcelReader.
        // // If previous run has set some sheet name in selectSheets(), its retaining that sheet name
        // // until it is replaced with new sheet name.
        // //
        // $app['excel.reader']->setSelectedSheets([]);

        // $app['excel.reader']->setSelectedSheetIndices([]);

        // $this->traceExcelReaderConfig();

        $sheets = $this->parseExcelFile($filePath, [], $startRow, 'none');

        $hasSingleSheet  = (count($sheets) === 1);
        $errorMessage    = 'Sheets keys: ' . implode('.', array_keys($sheets));

        assertTrue($hasSingleSheet, $errorMessage);

        //
        // We use head() instead of integer index as sheets might be
        // an associative array.
        //
        return head($sheets);

        // Uncomment this if we are enabling multiple sheets
        // $finalEntries = [];

        // foreach ($sheets as $sheet)
        // {
        //     $finalEntries = array_merge($finalEntries, $sheet);
        // }

        // return $finalEntries;
    }

    /**
     * Parses excel sheets at given path and returns array content.
     * Uses new phpoffice/phpspreadsheet package instead of maatwebsite/excel.
     * @param  string $filePath
     * @param int $numRowsToSkip
     * @return array
     */
    protected function parseExcelSheetsUsingPhpSpreadSheet($filePath, $numRowsToSkip = 0): array
    {
        $fileType = SpreadsheetIOFactory::identify($filePath);

        $reader = SpreadsheetIOFactory::createReader($fileType);

        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheetDefaultValueBinder);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        assertTrue($spreadsheet->getSheetCount() === 1);

        $rows = $spreadsheet->getActiveSheet()->toArray(null, false);

        $rows = array_slice($rows, $numRowsToSkip);

        $headers = array_values(array_shift($rows) ?? []);

        // No rows exists
        if (empty($headers) === true)
        {
            return [];
        }

        // Format rows as "heading key => value" kind of associative array
        foreach ($rows as & $row)
        {
            $row = array_combine($headers, array_values($row));
        }

        return $rows;
    }

    /**
     * Traces excel reader configuration, helps with debugging
     */
    protected function traceExcelReaderConfig()
    {
        $reader = app('excel.reader');

        $config = [
            'import_configs'       => config('excel.import'),
            'sheetsSelected'       => $reader->selectedSheets,
            'selectedSheetIndices' => $reader->selectedSheetIndices,
        ];

        $this->trace()->debug(TraceCode::EXCEL_READER_IMPORT_CONFIG, $config);
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

    protected function storeReconciledFile($file)
    {
        $filename = basename($file, '.txt');

        $dir = storage_path('files/settlement/reconciled');

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $time = Carbon::now(Timezone::IST)->format('H:i:s');

        $mode = $this->getMode();

        $newName = $dir . '/' . $filename . '_' . $mode.'_'.$time . '.txt';

        $res = rename($file, $newName);

        if ($res === false)
        {
            throw new Exception\RuntimeException(
                'Failed to rename file. File : ' . $file .
                ' Renamed name: ' . $newName);
        }

        return $newName;
    }

    protected function moveFile($file)
    {
        $uploadedFilePath = $file->getRealPath();

        $extension = $file->getClientOriginalExtension();

        $newFilepath = $this->getFileToReadFullPath($extension);

        $dir = FileStore\Utility::getStorageDir();

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $res = rename($uploadedFilePath, $newFilepath);

        if ($res === false)
        {
            throw new Exception\RuntimeException(
                'Failed to rename file. Uploaded name: ' . $uploadedFilePath .
                ' Renamed name: ' . $newFilepath);
        }

        return $newFilepath;
    }

    protected function getMode()
    {
        $app = App::getFacadeRoot();

        $mode = $app['basicauth']->getMode();

        return $mode;
    }

    protected function getFullFilePath($filename)
    {
        return FileStore\Utility::getStorageDir() . '/' . $filename;
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
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        );
    }
}
