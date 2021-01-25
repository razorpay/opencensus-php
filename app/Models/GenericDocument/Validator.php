<?php

namespace RZP\Models\GenericDocument;

use RZP\Base;
use RZP\Exception;
use RZP\Error\PublicErrorDescription;

class Validator extends Base\Validator
{
    protected static $uploadDocumentRules = [
        Constants::PURPOSE => 'required|string|max:255|custom',
        Constants::FILE    => 'required|file|max:5120',
    ];

    protected static $kycMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png'
    ];

    protected static $logoMimeRules = [
        Constants::FILE    => 'mimes:jpeg,jpg,png'
    ];

    protected $mimeValidators = [
        Constants::KYC_PROOF => 'kyc_mime',
        Constants::TRADEMARK_LOGO => 'logo_mime'
    ];

    public function validateMimeType(array $input)
    {
        $purpose = $input[Constants::PURPOSE];

        $validatorOperation = $this->mimeValidators[$purpose];

        $mimeValidationInput = [Constants::FILE => $input[Constants::FILE]];

        $this->validateInput($validatorOperation , $mimeValidationInput);
    }

    public function validatePurpose(string $attribute, $value)
    {
        if ($this->isValidPurpose($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_DOCUMENT_UPLOAD_PURPOSE_INVALID . ':' . $value
            );
        }
    }

    public function isValidPurpose(string $value)
    {
        return (in_array($value, Constants::PURPOSE_TYPE) === true);
    }
}