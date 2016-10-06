<?php

namespace RZP\Models\FileHandler;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

class Helper
{
    const STORAGE_DIRECTORY =  'files/file_handler/';

    public function getMimeType($file)
    {
        return $file->getMimeType();
    }

    public function getFileSize($file)
    {
        return $file->getSize();
    }

    public function getFileDetails($filePath, $input)
    {
        $fileDetails = [];

        $file = new File($filePath);

        $fileDetails[Entity::SIZE] = $this->getFileSize($file);

        $fileDetails[Entity::FORMAT] = $this->getMimeType($file);

        $fileDetails[Entity::COMMENTS] = $this->getValueOrDefault($input, 'comments');

        $fileDetails[Entity::NAME] = $filePath;

        $fileDetails[Entity::PASSWORD] = $this->getValueOrDefault($input, 'password');

        $fileDetails[Entity::ENCRYPTION_METHOD] = $this->getValueOrDefault($input, 'encryptionMethod', 'none');

        $fileDetails[Entity::METADATA] = $this->getValueOrDefault($input, 'metadata');

        return $fileDetails;
    }

    protected function getValueOrDefault($array, $key, $default = '')
    {
        $value = $default;

        if (isset($array[$key]) === true)
        {
            $value = $array[$key];
        }

        return $value;
    }

    public function writeToTextFile($filePrefix, $txt)
    {
        $name = $this->getFileToWriteName($filePrefix);

        $fullpath = $this->saveLocally($name, $txt);

        return $fullpath;
    }

    protected function saveLocally($name, $txt)
    {
        $fullpath = $this->getFullFilePath($name);

        Storage::put(self::STORAGE_DIRECTORY . $name, $txt, 'public');;

        return $fullpath;
    }

    protected function getFullFilePath($filename)
    {
        return $this->getStorageDir() . $filename;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $fullpath = $path . self::STORAGE_DIRECTORY;

        return $fullpath;
    }

    protected function getFileToWriteName($filePrefix)
    {
        return $this->getFileToWriteNameWithoutExt($filePrefix) . '.txt';
    }

    protected function getFileToWriteNameWithoutExt($filePrefix)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        return $filePrefix.'_'.$mode.'_'.$time;
    }

    protected function getMode()
    {
        return \BasicAuth::getMode();
    }
}
