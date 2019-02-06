<?php

namespace RZP\Models\Admin\File;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Entity as Constants;

class Validator extends Base\Validator
{
    protected static $validTypes = [
        Constants::DISPUTE,
    ];

    protected static $uploadRules = [
        Core::FILE => 'required|file|max:204800'
                        . '|mime_types:'
                            . 'text/csv,'
                            . 'text/plain,'
                            . 'application/vnd.ms-excel,'
                            . 'application/vnd.oasis.opendocument.spreadsheet,'
                            . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        . '|mimes:'
                            . 'xls,'
                            . 'xlsx,'
                            . 'csv,'
                            . 'txt',
    ];

    public function validateType(string $attribute, string $value)
    {
        if (in_array($value, self::$validTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid admin file type: ' . $value,
                $attribute);
        }
    }
}
