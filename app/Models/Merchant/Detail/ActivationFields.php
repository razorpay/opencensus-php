<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\Type as DocumentType;

class ActivationFields
{
    const FIELD_DISPLAY_NAME = [
        DocumentType::ADDRESS_PROOF_URL            => 'Cancelled Cheque/Bank Statement',
        DocumentType::BUSINESS_PROOF_URL           => 'Business Registration Proof',
        DocumentType::PROMOTER_ADDRESS_URL         => 'Authorized Signatory\'s Address Proof',
        DocumentType::BUSINESS_OPERATION_PROOF_URL => 'Business Operation Proof',
        DocumentType::BUSINESS_PAN_URL             => 'Company Pan',
        DocumentType::PROMOTER_PROOF_URL           => 'Promoter Pan',
        DocumentType::FORM_12A_URL                 => 'Form 12A Allotment Letter',
        DocumentType::FORM_80G_URL                 => 'Form 80G Allotment Letter',
        DocumentType::AADHAR_FRONT                 => 'Aadhaar Front Side',
        DocumentType::AADHAR_BACK                  => 'Aadhaar Back Side',
        DocumentType::PASSPORT_BACK                => 'Passport Last Page',
        DocumentType::PASSPORT_FRONT               => 'Passport First Page',
        DocumentType::VOTER_ID_FRONT               => 'Voter Id Front Side',
        DocumentType::VOTER_ID_BACK                => 'Voter Id Back Side',
        DocumentType::CANCELLED_CHEQUE             => 'Cancelled Cheque',
        DocumentType::ARTICLE_OF_ASSOCIATION       => 'Board Resolution for Authorization',
        DocumentType::MEMORANDUM_OF_ASSOCIATION    => 'Board Resolution for Authorization',
        DocumentType::BOARD_RESOLUTION             => 'Board Resolution for Authorization',

        DocumentType::SEBI_REGISTRATION_CERTIFICATE      => 'SEBI Registration Certificate',
        DocumentType::IRDAI_REGISTRATION_CERTIFICATE     => 'IRDAI Registration Certificate',
        DocumentType::FFMC_LICENSE                       => 'FFMC License',
        DocumentType::NBFC_REGISTRATION_CERTIFICATE      => 'NBFC Registration Certificate',
        DocumentType::AMFI_CERTIFICATE                   => 'AMFI Certificate',
        DocumentType::IATA_CERTIFICATE                   => 'IATA Certificate',
        DocumentType::AFFILIATION_CERTIFICATE            => 'Affiliation Certificate',

        //sla refer to service level agreement
        DocumentType::SLA_SEBI_REGISTRATION_CERTIFICATE  => 'Service Level Agreement with a SEBI Certified Company',
        DocumentType::SLA_IRDAI_REGISTRATION_CERTIFICATE => 'Service Level Agreement with an IRDAI Certified Company',
        DocumentType::SLA_FFMC_LICENSE                   => 'sService Level Agreement with a FFMC Certified Company',
        DocumentType::SLA_NBFC_REGISTRATION_CERTIFICATE  => 'Service Level Agreement with a NBFC Certified Company',
        DocumentType::SLA_AMFI_CERTIFICATE               => 'Service Level Agreement with an AMFI Certified Company',
        DocumentType::SLA_IATA_CERTIFICATE               => 'Service Level Agreement with an IATA Certified Company',
    ];

    public static function getFieldDisplayName(string $field): string
    {
        if (isset(self::FIELD_DISPLAY_NAME[$field]) === true)
        {
            return self::FIELD_DISPLAY_NAME[$field];
        }

        return title_case(str_replace('_', ' ', $field));
    }
}
