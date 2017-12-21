<?php

namespace RZP\Models\Dispute\File;

use RZP\Base;
use RZP\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Validator extends Base\Validator
{
    const operationUploadFile = 'upload_file';

    protected static $createRules = [
        Entity::DISPUTE_ID         => 'required|string|max:14',
        Entity::URL                => 'required|string|max:255',
        Entity::NAME               => 'required|string|max:50',
        Entity::CATEGORY           => 'required|string|custom',
    ];

    protected static $uploadFileRules = [
        Entity::FILE               => 'required|file|max:10485760|mime_types:'
                                        . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                                        . 'application/msword,'
                                        . 'application/pdf,'
                                        . 'image/png,'
                                        . 'image/jpg,'
                                        . 'image/jpeg,',
        Entity::NAME               => 'required|string|max:50',
        Entity::CATEGORY           => 'required|string|custom',

    ];

    protected function validateCategory(string $attribute, string $value)
    {
        if (Category::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute file category: ' . $value);
        }
    }

    public function validateFileDetails($file)
    {
        $this->validateInput(self::operationUploadFile, $file);
    }

    public function validateNumberOfFiles(array $files)
    {
        if (sizeof($files) === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain any files to be uploaded',
                Entity::FILES);
        }
        else if (sizeof($files) > Entity::MAX_NUM_FILES)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input files exceeds maximum allowed number of files : '. Entity::MAX_NUM_FILES,
                Entity::FILES);
        }
    }
}
