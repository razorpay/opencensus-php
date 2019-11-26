<?php

namespace RZP\Models\Merchant\Document;

class Type
{
    const SEBI_REGISTRATION_CERTIFICATE  = 'sebi_registration_certificate';
    const IRDAI_REGISTRATION_CERTIFICATE = 'irdai_registration_certificate';
    const FFMC_LICENSE                   = 'ffmc_license';
    const NBFC_REGISTRATION_CERTIFICATE  = 'nbfc_registration_certificate';
    const AMFI_CERTIFICATE               = 'amfi_certificate';
    const PPI_LICENSE                    = 'ppi_license';
    const DRIVER_LICENSE_FRONT           = 'driver_license_front';
    const DRIVER_LICENSE_BACK            = 'driver_license_back';
    const AADHAR_FRONT                   = 'aadhar_front';
    const AADHAR_BACK                    = 'aadhar_back';
    const PASSPORT_BACK                  = 'passport_back';
    const PASSPORT_FRONT                 = 'passport_front';
    const VOTER_ID_FRONT                 = 'voter_id_front';
    const VOTER_ID_BACK                  = 'voter_id_back';
    const CANCELLED_CHECK                = 'cancelled_check';
    const BUSINESS_PROOF_URL             = 'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL   = 'business_operation_proof_url';
    const BUSINESS_PAN_URL               = 'business_pan_url';
    const ADDRESS_PROOF_URL              = 'address_proof_url';
    const PROMOTER_PROOF_URL             = 'promoter_proof_url';
    const PROMOTER_PAN_URL               = 'promoter_pan_url';
    const PROMOTER_ADDRESS_URL           = 'promoter_address_url';
    const FORM_12A_URL                   = 'form_12a_url';
    const FORM_80G_URL                   = 'form_80g_url';


    /**
     * Following documents needs to perform for OCR
     * @var array
     */
    protected static $documentsToPerformOcr = [
        self::AADHAR_FRONT,
        self::PASSPORT_FRONT,
        self::VOTER_ID_FRONT,
    ];

    public static function isValid($value)
    {
        $key = __CLASS__ . '::' . strtoupper($value);

        return ((defined($key) === true) and (constant($key) === $value));
    }

    public static function isDocumentTypeToPerformOcr($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$documentsToPerformOcr, true);
    }
}
