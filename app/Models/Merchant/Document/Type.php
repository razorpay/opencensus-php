<?php

namespace RZP\Models\Merchant\Document;

class DocumentType
{
    const SEBI_REGISTRATION_CERTIFICATE  = 'sebi_registration_certificate';
    const IRDAI_REGISTRATION_CERTIFICATE = 'irdai_registration_certificate';
    const FFMC_LICENSE                   = 'ffmc_license';
    const NBFC_REGISTRATION_CERTIFICATE  = 'nbfc_registration_certificate';
    const AMFI_CERTIFICATE               = 'amfi_certificate';
    const PPI_LICENSE                    = 'ppi_license';
    const BUSINESS_PROOF_URL             = 'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL   = 'business_operation_proof_url';
    const BUSINESS_PAN_URL               = 'business_pan_url';
    const ADDRESS_PROOF_URL              = 'address_proof_url';
    const PROMOTER_PROOF_URL             = 'promoter_proof_url';
    const PROMOTER_PAN_URL               = 'promoter_pan_url';
    const PROMOTER_ADDRESS_URL           = 'promoter_address_url';
    const FORM_12A_URL                   = 'form_12a_url';
    const FORM_80G_URL                   = 'form_80g_url';

    public static function isValid($value)
    {
        $key = __CLASS__ . '::' . strtoupper($value);

        return ((defined($key) === true) and (constant($key) === $value));
    }

}
