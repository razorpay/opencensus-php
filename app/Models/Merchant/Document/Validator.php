<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base;
use RZP\Exception;
use RZP\Error\PublicErrorDescription;

class Validator extends Base\Validator
{
    const MAXIMUM_FILE_SIZE = 4 * 1024 * 1024; // 4MB

    protected static $createRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
        Entity::SOURCE        => 'required_with:file_store_id|string|custom',
    ];

    protected static $editRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'sometimes|file|mimes:pdf,jpeg,jpg,png',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
        Entity::SOURCE        => 'required_with:file_store_id|string|custom',
    ];

    protected static $uploadDocumentRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png',
    ];

    protected static $aadharUploadRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file',
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

    public function validateProofType($value, string $entityType)
    {
        if(Type::isValidProofType($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PROOF_TYPE_INVALID . ': ' . $value
            );
        }

        if (Type::PROOF_TYPE_ENTITY_MAPPING[$value] !== $entityType)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PROOF_TYPE_NOT_SUPPORTED . ': ' . $value
            );
        }
    }

    public function validateFileSize($file)
    {
        $fileSize = filesize($file);

        if ($fileSize === false)
        {
            throw new Exception\BadRequestValidationFailureException('Error occurred while validating file');
        }

        if ($fileSize > self::MAXIMUM_FILE_SIZE)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Maximum file size supported : ' . (self::MAXIMUM_FILE_SIZE) / (1024 * 1024) . 'MB'
            );
        }
    }
}
