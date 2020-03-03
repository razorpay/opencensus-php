<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base;
use RZP\Exception;
use RZP\Error\PublicErrorDescription;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
        Entity::SOURCE        => 'required_with:file_store_id|string|custom',
    ];

    protected static $editRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
        Entity::SOURCE        => 'required_with:file_store_id|string|custom',
    ];

    /**
     * @param string $attribute
     * @param        $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateDocumentType(string $attribute, $value)
    {
        if (Type::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_DOCUMENT_TYPE_INVALID . ':' . $value
            );
        }
    }

    /**
     * @param string $attribute
     * @param        $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSource(string $attribute, $value)
    {
        Source::validateSource($value);
    }
}
