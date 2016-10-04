<?php

namespace RZP\Models\FileHandler;

use Symfony\Component\HttpFoundation\File\File;

class Helper
{
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

        $fileDetails[Entity::METADATA] = $this->getValueOrDefault($input, 'metaData');

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
}
