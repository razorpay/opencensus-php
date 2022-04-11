<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\NeedsClarification;

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
        Entity::DOCUMENT_DATE => 'sometimes|integer'
    ];

    protected static $uploadFilesByAgentRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file',
        Entity::MERCHANT_ID   => 'required|string'
    ];

    protected static $uploadDocumentRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png',
    ];

    protected static $aadharUploadRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE          => 'required|file',
    ];

    protected static $firsDocumentFetchRequestRules = [
        'month'       => 'required|min:1|max:12',
        'year'        => 'required|digits:4',
    ];

    protected static $firsDocumentDownloadRequestRules = [
        'month'       => 'required|min:1|max:12',
        'year'        => 'required|digits:4',
        'document_id' => 'required'
    ];

    protected static $firsZippingCronRequestRules = [
        'month'         => 'required_with:force_create|min:1|max:12',
        'year'          => 'required_with:force_create|digits:4',
        'merchant_ids'  => 'required_with:force_create|array',
        'force_create'  => 'sometimes|boolean'
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

    public function validateNeedsClarificationRespondedIfApplicable(Merchant\Entity $merchant, array $input)
    {
        $merchantDetails = $merchant->merchantDetail;

        if (empty($merchantDetails) === true || $merchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            return;
        }

        $clarificationReasons = (new NeedsClarification\Core)->getNonAcknowledgedNCFields($merchant, $merchantDetails);

        if($clarificationReasons[Merchant\Constants::COUNT] === 0)
        {
            return;
        }

        $ncDocuments = $clarificationReasons['documents'] ?? [];

        $documentType = $input[Constants::DOCUMENT_TYPE];

        if (array_key_exists($documentType, $ncDocuments) === false)
        {
            $tracePayload = [
                'provided_document_type' => $documentType,
                'accepted_document_types' => array_keys($ncDocuments)
            ];

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_DOCUMENTS_ARE_ALLOWED, null, $tracePayload);
        }
    }
}
