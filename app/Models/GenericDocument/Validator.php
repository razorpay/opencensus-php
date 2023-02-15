<?php

namespace RZP\Models\GenericDocument;

use RZP\Base;
use RZP\Exception;
use RZP\Error\PublicErrorDescription;

class Validator extends Base\Validator
{
    protected static $uploadDocumentRules = [
        Constants::PURPOSE => 'required|string|max:255|custom',
        Constants::FILE    => 'required|file|max:50000'
    ];

    //The expiry is in minutes. UFH accepts duration of singed url in minutes
    protected static $fetchDocumentRules = [
        Constants::EXPIRY => 'sometimes|integer|min:1|max:120'
    ];

    protected static $kycMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png,jfif'
    ];

    protected static $logoMimeRules = [
        Constants::FILE    => 'mimes:jpeg,jpg,png,jfif'
    ];

    protected static $ieMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png,jfif'
    ];

    protected static $disputeEvidenceMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png,jfif',
    ];

    protected static $merchantWorkflowClarificationMimeRules = [
        Constants::FILE  => 'mime_types:'
                            . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                            . 'application/msword,'
                            . 'application/pdf,'
                            . 'application/x-pdf,'
                            . 'application/xml,'
                            . 'application/octet-stream,'
                            . 'application/vnd.rn-realmedia,'
                            . 'image/png,'
                            . 'image/jpg,'
                            . 'image/jpeg,'
                            . 'image/jfif,'
                            . 'text/xml,'
                            . 'text/csv,'
                            . 'text/plain,'
                            . 'video/3gpp2,'
                            . 'video/3gpp,'
                            . 'video/x-msvideo,'
                            . 'video/x-flv,'
                            . 'video/mp4,'
                            . 'video/m4v,'
                            . 'video/x-matroska,'
                            . 'video/quicktime,'
                            . 'video/mp4,'
                            . 'video/mpeg,'
                            . 'video/mpeg,'
                            . 'video/x-ms-wmv'
    ];

    protected static $b2bExportInvoiceMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png,jfif',
    ];

    protected static $apmOnboardingMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png,jfif',
    ];

    protected static $opgspInvoiceMimeRules = [
        Constants::FILE    => 'mimes:pdf,jpeg,jpg,png',
    ];

    protected $mimeValidators = [
        Constants::KYC_PROOF                        => 'kyc_mime',
        Constants::TRADEMARK_LOGO                   => 'logo_mime',
        Constants::DISPUTE_EVIDENCE                 => 'dispute_evidence_mime',
        Constants::INTERNATIONAL_ENABLEMENT         => 'ie_mime',
        Constants::MERCHANT_WORKFLOW_CLARIFICATION  => 'merchant_workflow_clarification_mime',
        Constants::B2B_EXPORT_INVOICE               => 'b2b_export_invoice_mime',
        Constants::APM_ONBOARDING                   => 'apm_onboarding_mime',
        Constants::OPGSP_INVOICE                    => 'opgsp_invoice_mime',
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
