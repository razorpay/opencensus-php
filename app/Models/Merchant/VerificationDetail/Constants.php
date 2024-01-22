<?php


namespace RZP\Models\Merchant\VerificationDetail;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as ValidationConstants;

class Constants
{
    // Artefact Types
    const SHOP_ESTABLISHMENT = 'shop_establishment';
    const PARTNERSHIP_DEED   = 'partnership_deed';
    const GST_CERTIFICATE    = 'gst_certificate';
    const GSTIN              = 'gstin';

    // Artefact Identifiers
    const DOC    = 'doc';
    const NUMBER = 'number';

    const DOCUMENT_DETAILS = 'document_details';

    //Confidence score
    const MCC_CONFIDENCE_SCORE_THRESHOLD = 0.9;

    const ID                     = 'id';
    const STATUS                 = 'status';
    const CATEGORY_RESULT        = 'category_result';
    const WEBSITE_CATEGORISATION = 'website_categorisation';
    const CONFIDENCE_SCORE       = 'confidence_score';
    const CATEGORY               = 'category';
    const SUBCATEGORY            = 'subcategory';
    const PREDICTED_MCC          = 'predicted_mcc';

    const SHOP_ESTABLISHMENT_DOC_STATUS                     = "shop_establishment_doc_status";
    const GST_DOC_STATUS                                    = "gst_doc_status";
    const TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE_DOC_STATUS = "trust_society_ngo_business_certificate";
    const CERTIFICATE_OF_INCORPORATION_DOC_STATUS           = "certificate_of_incorporation_doc_status";
    const PARTNERSHIP_DEED_DOC_STATUS                       = "partnership_deed_doc_status";
    const NEGATIVE_KEYWORDS_STATUS                          = "negative_Keywords_status";
    const WEBSITE_POLICY_STATUS                             = "website_policy_status";
    const MCC_CATEGORIZATION_WEBSITE_STATUS                 = "mcc_categorization_website_status";
    const SIGNATORY_VALIDATION_STATUS                       = "signatory_validation_status";

    const SIGNATORY_ALLOWED_ARTEFACTS = [
        Constant::MSME . '-' . ValidationConstants::PROOF,
        Constant::CIN . '-' . ValidationConstants::IDENTIFIER,
        Constant::GSTIN . '-' . ValidationConstants::IDENTIFIER,
        Constant::BANK_ACCOUNT . '-' . ValidationConstants::PROOF,
        Constant::LLP_DEED . '-' . ValidationConstants::IDENTIFIER,
        Constant::PARTNERSHIP_DEED . '-' . ValidationConstants::PROOF,
        Constant::BANK_ACCOUNT . '-' . ValidationConstants::IDENTIFIER,
        Constant::SHOP_ESTABLISHMENT . '-' . ValidationConstants::IDENTIFIER
    ];

    const ARTEFACTS_STATUS_MAPPING = [
        Constant::SHOP_ESTABLISHMENT . '_' . Constants::DOC                     => Constants::SHOP_ESTABLISHMENT_DOC_STATUS,
        Constant::GSTIN . '_' . Constants::DOC                                  => Constants::GST_DOC_STATUS,
        Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE . '_' . Constants::DOC => Constants::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE_DOC_STATUS,
        Constant::CERTIFICATE_OF_INCORPORATION . '_' . Constants::DOC           => Constants::CERTIFICATE_OF_INCORPORATION_DOC_STATUS,
        Constant::PARTNERSHIP_DEED . '_' . Constants::DOC                       => Constants::PARTNERSHIP_DEED_DOC_STATUS,
        Constant::NEGATIVE_KEYWORDS . '_' . Constants::NUMBER                   => Constants::NEGATIVE_KEYWORDS_STATUS,
        Constant::WEBSITE_POLICY . '_' . Constants::NUMBER                      => Constants::WEBSITE_POLICY_STATUS,
        Constant::MCC_CATEGORISATION_WEBSITE . '_' . Constants::NUMBER          => Constants::MCC_CATEGORIZATION_WEBSITE_STATUS,
        Constant::SIGNATORY_VALIDATION . '_' . Constants::NUMBER                => Constants::SIGNATORY_VALIDATION_STATUS
    ];
}
