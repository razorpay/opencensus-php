<?php

namespace RZP\Models\Gateway\File\Processor;

use Storage;
use ZipArchive;

use RZP\Models\FileStore;
use RZP\Models\FileStore\Utility;
use RZP\Exception\RuntimeException;

trait FileHandler
{
    protected function getInitialLine(string $glue = '|'): string
    {
        $data = static::HEADERS;

        $line = implode($glue, $data) . "\r\n";

        return $line;
    }

    protected function getTextData($data, $prependLine = '', string $glue = '|'): string
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, $glue, $ignoreLastNewline);

        return $prependLine . $txt;
    }

    protected function generateText($data, $glue = '|', $ignoreLastNewline = false): string
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
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

    protected function generateZipFile($zipFileTarget)
    {
        $files = Storage::files($zipFileTarget);

        $zipFileLocalName = basename($zipFileTarget);

        $zipFileLocalPath = $this->getLocalSaveDir() . DIRECTORY_SEPARATOR . $zipFileLocalName . '.zip';

        $zipFileS3Name = self::S3_PATH . basename($zipFileTarget);

        $zip = new ZipArchive();

        if ($zip->open($zipFileLocalPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(
                'Could not create Papernach zip file',
                [
                    'filename' => $zipFileLocalPath
                ]);
        }

        $basePath = storage_path('app') . '/';

        foreach ($files as $file)
        {
            $filePath = $basePath . $file;

            $zip->addFile($filePath, basename($file));
        }

        $zip->close();

        $zipCreator = new FileStore\Creator;

        $zipCreator->extension(static::EXTENSION)
                   ->localFilePath($zipFileLocalPath)
                   ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::EXTENSION][0])
                   ->name($zipFileS3Name)
                   ->store(FileStore\Store::S3)
                   ->type(static::FILE_TYPE)
                   ->entity($this->gatewayFile)
                   ->metadata(static::FILE_METADATA)
                   ->save();

        $file = $zipCreator->getFileInstance();

        $this->fileStore[] = $file->getId();

        $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

        unlink($zipFileLocalPath); // nosemgrep : php.lang.security.unlink-use.unlink-use
    }

    protected function getLocalSaveDir(): string
    {
        $dirPath = storage_path('files/nach');

        if (file_exists($dirPath) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dirPath, 0777, true]);
        }

        return $dirPath;
    }
}
