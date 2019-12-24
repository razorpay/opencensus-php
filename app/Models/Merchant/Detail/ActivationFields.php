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
        DocumentType::PROMOTER_ADDRESS_URL         => 'Promoter Address Proof',
        DocumentType::FORM_12A_URL                 => 'Form 12A Allotment Letter',
        DocumentType::FORM_80G_URL                => 'Form 80G Allotment Letter',
        DocumentType::AADHAR_FRONT                 => 'Aadhaar Front Side',
        DocumentType::AADHAR_BACK                  => 'Aadhaar Back Side',
        DocumentType::PASSPORT_BACK                => 'Passport Last Page',
        DocumentType::PASSPORT_FRONT               => 'Passport First Page',
        DocumentType::VOTER_ID_FRONT               => 'Voter Id Front Side',
        DocumentType::VOTER_ID_BACK                => 'Voter Id Back Side',
        DocumentType::CANCELLED_CHEQUE             => 'Cancelled Cheque',
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
