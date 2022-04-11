<?php

namespace RZP\Models\Merchant\Document;

use RZP\Constants\Entity as E;

class Type
{
    const SEBI_REGISTRATION_CERTIFICATE  = 'sebi_registration_certificate';
    const IRDAI_REGISTRATION_CERTIFICATE = 'irdai_registration_certificate';
    const FFMC_LICENSE                   = 'ffmc_license';
    const NBFC_REGISTRATION_CERTIFICATE  = 'nbfc_registration_certificate';
    const AMFI_CERTIFICATE               = 'amfi_certificate';

    //sla refer to service level agreement
    const SLA_SEBI_REGISTRATION_CERTIFICATE  = 'sla_sebi_registration_certificate';
    const SLA_IRDAI_REGISTRATION_CERTIFICATE = 'sla_irdai_registration_certificate';
    const SLA_FFMC_LICENSE                   = 'sla_ffmc_license';
    const SLA_NBFC_REGISTRATION_CERTIFICATE  = 'sla_nbfc_registration_certificate';
    const SLA_AMFI_CERTIFICATE               = 'sla_amfi_certificate';
    const SLA_IATA_CERTIFICATE               = 'sla_iata_certificate';

    //optional documents
    const AFFILIATION_CERTIFICATE = 'affiliation_certificate';
    const IATA_CERTIFICATE        = 'iata_certificate';

    const PPI_LICENSE                    = 'ppi_license';
    const DRIVER_LICENSE_FRONT           = 'driver_license_front';
    const DRIVER_LICENSE_BACK            = 'driver_license_back';
    const AADHAR_FRONT                   = 'aadhar_front';
    const AADHAR_BACK                    = 'aadhar_back';
    const AADHAR_XML                     = 'aadhar_xml';
    const AADHAR_ZIP                     = 'aadhar_zip';
    const PASSPORT_BACK                  = 'passport_back';
    const PASSPORT_FRONT                 = 'passport_front';
    const VOTER_ID_FRONT                 = 'voter_id_front';
    const VOTER_ID_BACK                  = 'voter_id_back';
    const CANCELLED_CHEQUE               = 'cancelled_cheque';
    const BUSINESS_PROOF_URL             = 'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL   = 'business_operation_proof_url';
    const BUSINESS_PAN_URL               = 'business_pan_url';
    const ADDRESS_PROOF_URL              = 'address_proof_url';
    const PROMOTER_PROOF_URL             = 'promoter_proof_url';
    const PROMOTER_PAN_URL               = 'promoter_pan_url';
    const PROMOTER_ADDRESS_URL           = 'promoter_address_url';
    const FORM_12A_URL                   = 'form_12a_url';
    const FORM_80G_URL                   = 'form_80g_url';
    const MEMORANDUM_OF_ASSOCIATION      = 'memorandum_of_association';
    const ARTICLE_OF_ASSOCIATION         = 'article_of_association';
    const BOARD_RESOLUTION               = 'board_resolution';

    // For KYC service integration
    const PERSONAL_PAN                   = 'personal_pan';
    const AADHAAR                        = 'aadhaar';
    const PASSPORT                       = 'passport';
    const VOTERS_ID                      = 'voters_id';
    const DRIVERS_LICENSE                = 'drivers_license';

    const SHOP_ESTABLISHMENT_CERTIFICATE = "shop_establishment_certificate";
    const GST_CERTIFICATE                = "gst_certificate";
    const MSME_CERTIFICATE               = "msme_certificate";
    const BANK_STATEMENT                 = "bank_statement";

    //proof types
    const INDIVIDUAL_PROOF_OF_ADDRESS           = 'individual_proof_of_address';
    const INDIVIDUAL_PROOF_OF_IDENTIFICATION    = 'individual_proof_of_identification';
    const BUSINESS_PROOF_OF_IDENTIFICATION      = 'business_proof_of_identification';
    const ADDITIONAL_DOCUMENTS                  = 'additional_documents';
    const POI_IDENTIFICATION_NUMBER             = 'poi_identification_number';
    const POA_IDENTIFICATION_NUMBER             = 'poa_identification_number';
    const IDENTIFICATION_NUMBER                 = 'identification_number';

    const OTHER              = 'other';
    const WEBSITE_SCREENSHOT = 'website_screenshot';
    //FIRS Documents
    const FIRS_FILE                             = 'firs_file';
    const FIRS_ZIP                              = 'firs_zip';

    const FIRS_ICICI_FILE                             = 'firs_icici_file';
    const FIRS_ICICI_ZIP                              = 'firs_icici_zip';
    
    const PROOF_TYPES = [
        self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,
        self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::ADDITIONAL_DOCUMENTS,
    ];

    const PROOF_TYPE_ENTITY_MAPPING = [
        self::INDIVIDUAL_PROOF_OF_ADDRESS          => E::STAKEHOLDER,
        self::INDIVIDUAL_PROOF_OF_IDENTIFICATION   => E::STAKEHOLDER,
        self::BUSINESS_PROOF_OF_IDENTIFICATION     => E::MERCHANT,
        self::ADDITIONAL_DOCUMENTS                 => E::MERCHANT,
    ];

    const BANK_PROOF_DOCUMENTS = [
        self::CANCELLED_CHEQUE,
        self::BANK_STATEMENT,
    ];

    const VALID_DOCUMENTS = [
        self::SEBI_REGISTRATION_CERTIFICATE,
        self::IRDAI_REGISTRATION_CERTIFICATE,
        self::FFMC_LICENSE,
        self::NBFC_REGISTRATION_CERTIFICATE,
        self::AMFI_CERTIFICATE,

        self::SLA_SEBI_REGISTRATION_CERTIFICATE,
        self::SLA_IRDAI_REGISTRATION_CERTIFICATE,
        self::SLA_FFMC_LICENSE,
        self::SLA_NBFC_REGISTRATION_CERTIFICATE,
        self::SLA_AMFI_CERTIFICATE,
        self::SLA_IATA_CERTIFICATE,

        self::AFFILIATION_CERTIFICATE,
        self::IATA_CERTIFICATE,

        self::PPI_LICENSE,
        self::DRIVER_LICENSE_BACK,
        self::DRIVER_LICENSE_FRONT,
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
        self::AADHAR_XML,
        self::AADHAR_ZIP,

        self::PASSPORT_FRONT,
        self::PASSPORT_BACK,
        self::VOTER_ID_FRONT,
        self::VOTER_ID_BACK,
        self::CANCELLED_CHEQUE,
        self::BUSINESS_PROOF_URL,
        self::BUSINESS_OPERATION_PROOF_URL,
        self::BUSINESS_PAN_URL,
        self::ADDRESS_PROOF_URL,
        self::PROMOTER_PROOF_URL,
        self::PROMOTER_PAN_URL,
        self::PROMOTER_ADDRESS_URL,
        self::FORM_12A_URL,
        self::FORM_80G_URL,
        self::MEMORANDUM_OF_ASSOCIATION,
        self::ARTICLE_OF_ASSOCIATION,
        self::BOARD_RESOLUTION,
        self::PERSONAL_PAN,

        self::SHOP_ESTABLISHMENT_CERTIFICATE,
        self::GST_CERTIFICATE,
        self::MSME_CERTIFICATE,
        self::BANK_STATEMENT,

        self::FIRS_FILE,
        self::FIRS_ZIP,

        self::WEBSITE_SCREENSHOT,
        self::OTHER,

        self::FIRS_ICICI_FILE,
        self::FIRS_ICICI_ZIP,
    ];

    const DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING = [
        self::SEBI_REGISTRATION_CERTIFICATE    => self::ADDITIONAL_DOCUMENTS,
        self::IRDAI_REGISTRATION_CERTIFICATE   => self::ADDITIONAL_DOCUMENTS,
        self::FFMC_LICENSE                     => self::ADDITIONAL_DOCUMENTS,
        self::NBFC_REGISTRATION_CERTIFICATE    => self::ADDITIONAL_DOCUMENTS,
        self::AMFI_CERTIFICATE                 => self::ADDITIONAL_DOCUMENTS,

        self::SLA_SEBI_REGISTRATION_CERTIFICATE   => self::ADDITIONAL_DOCUMENTS,
        self::SLA_IRDAI_REGISTRATION_CERTIFICATE  => self::ADDITIONAL_DOCUMENTS,
        self::SLA_FFMC_LICENSE                    => self::ADDITIONAL_DOCUMENTS,
        self::SLA_NBFC_REGISTRATION_CERTIFICATE   => self::ADDITIONAL_DOCUMENTS,
        self::SLA_AMFI_CERTIFICATE                => self::ADDITIONAL_DOCUMENTS,
        self::SLA_IATA_CERTIFICATE                => self::ADDITIONAL_DOCUMENTS,

        self::AFFILIATION_CERTIFICATE             => self::ADDITIONAL_DOCUMENTS,
        self::IATA_CERTIFICATE                    => self::ADDITIONAL_DOCUMENTS,

        self::PPI_LICENSE                     => self::ADDITIONAL_DOCUMENTS,
        self::DRIVER_LICENSE_BACK             => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::DRIVER_LICENSE_FRONT            => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_FRONT                    => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_BACK                     => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_ZIP                      => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_XML                      => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::PASSPORT_FRONT                  => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::PASSPORT_BACK                   => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::VOTER_ID_FRONT                  => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::VOTER_ID_BACK                   => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::CANCELLED_CHEQUE                => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_PROOF_URL              => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::BUSINESS_OPERATION_PROOF_URL    => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_PAN_URL                => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::ADDRESS_PROOF_URL               => self::ADDITIONAL_DOCUMENTS,
        self::PROMOTER_PROOF_URL              => self::ADDITIONAL_DOCUMENTS,
        self::PROMOTER_PAN_URL                => self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,
        self::PROMOTER_ADDRESS_URL            => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::FORM_12A_URL                    => self::ADDITIONAL_DOCUMENTS,
        self::FORM_80G_URL                    => self::ADDITIONAL_DOCUMENTS,
        self::MEMORANDUM_OF_ASSOCIATION       => self::ADDITIONAL_DOCUMENTS,
        self::ARTICLE_OF_ASSOCIATION          => self::ADDITIONAL_DOCUMENTS,
        self::BOARD_RESOLUTION                => self::ADDITIONAL_DOCUMENTS,
        self::PERSONAL_PAN                    => self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,

        self::SHOP_ESTABLISHMENT_CERTIFICATE  => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::GST_CERTIFICATE                 => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::MSME_CERTIFICATE                => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::BANK_STATEMENT                  => self::ADDITIONAL_DOCUMENTS,

        self::FIRS_FILE                       => self::ADDITIONAL_DOCUMENTS,
        self::FIRS_ZIP                        => self::ADDITIONAL_DOCUMENTS,

        self::OTHER                           => self::ADDITIONAL_DOCUMENTS,
        self::WEBSITE_SCREENSHOT              => self::ADDITIONAL_DOCUMENTS,
        
        self::FIRS_ICICI_FILE                 => self::ADDITIONAL_DOCUMENTS,
        self::FIRS_ICICI_ZIP                  => self::ADDITIONAL_DOCUMENTS,
    ];

    /**
     * Following documents needs to perform for OCR
     * @var array
     */
    protected static $documentsToPerformOcr = [
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
        self::PASSPORT_FRONT,
        self::VOTER_ID_FRONT,
        self::MSME_CERTIFICATE,
        self::SHOP_ESTABLISHMENT_CERTIFICATE,
        self::GST_CERTIFICATE,
        self::BUSINESS_PROOF_URL
    ];

    protected static $poaDocuments = [
        self::AADHAR_FRONT,
        self::PASSPORT_FRONT,
        self::VOTER_ID_FRONT,
    ];

    protected static $jointValidationDocuments = [
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
    ];

    public static function isValid($value)
    {
        return (in_array($value, self::VALID_DOCUMENTS) === true);
    }

    public static function getRelatedDocumentType($documentType)
    {
         if ($documentType === self::AADHAR_FRONT) {
             return self::AADHAR_BACK;
         }
         return self::AADHAR_FRONT;
    }

    public static function isJointValidationDocumentType($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$jointValidationDocuments, true);
    }

    public static function isDocumentTypeToPerformOcr($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$documentsToPerformOcr, true);
    }

    public static function isPoaDocument($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$poaDocuments, true);
    }

    public static function isValidProofType($value)
    {
        return (in_array($value, self::PROOF_TYPES) === true);
    }

    public static function getValidDocumentForEntity(string $entityType)
    {
        $proofMappings = [];

        foreach (self::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING as $documentType => $proofType)
        {
            $proofMappings[$proofType][] = $documentType;
        }

        $proofs = [];
        foreach (self::PROOF_TYPE_ENTITY_MAPPING as $proofType => $entity)
        {
            if ($entity === $entityType)
            {
                $proofs[$proofType] = $entity;
            }
        }

        $validMappings = array_intersect_key($proofMappings, $proofs);

        return array_reduce($validMappings, function ($array, $item){
            return array_merge($array, $item);
        }, []);

    }
}
