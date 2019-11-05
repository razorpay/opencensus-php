<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
    ];

    protected static $editRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
    ];

    public function validateDocumentType(string $attribute, $value)
    {
        if ( Type::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_DOCUMENT_TYPE_INVALID . ':' . $value
            );
        }
    }
}
