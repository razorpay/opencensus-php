<?php

namespace RZP\Models\Dispute\File;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Dispute;

class Validator extends Base\Validator
{
    // Upper limit for number of files uploaded ia single request
    const MAX_NUM_FILES = 10;

    const OPERATION_UPLOAD_FILE = 'upload_file';
    const OPERATION_INPUT_FILES = 'input_files';

    protected static $createRules = [
        Core::FILE_ID  => 'required|string|max:50',
        Core::NAME     => 'required|string|max:50',
        Core::CATEGORY => 'required|string|custom',
    ];

    protected static $uploadFileRules = [
        Core::FILE     => 'required|file|max:10485760|mime_types:'
                              . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                              . 'application/msword,'
                              . 'application/pdf,'
                              . 'image/png,'
                              . 'image/jpg,'
                              . 'image/jpeg,',
        Core::NAME     => 'required|string|max:50',
        Core::CATEGORY => 'required|string|custom',

    ];

    protected static $inputFilesRules = [
        Core::FILES => 'required|array|between:1,'.self::MAX_NUM_FILES,
    ];

    protected function validateCategory(string $attribute, string $value)
    {
        if (Category::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute file category: ' . $value,
                $attribute);
        }
    }

    public function validateFileDetails(array $file)
    {
        $this->validateInput(self::OPERATION_UPLOAD_FILE, $file);
    }

    public function validateFilesInput(array $files)
    {
        $this->validateInput(self::OPERATION_INPUT_FILES, [Core::FILES => $files]);
    }

    public function validateDisputeForFileDelete(Dispute\Entity $dispute)
    {
        if ($dispute->getStatus() !== Dispute\Status::OPEN)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Files can be deleted only when dispute is open',
                Dispute\Entity::STATUS,
                [
                    Dispute\Entity::STATUS => $dispute->getStatus(),
                ]);
        }
    }
}
