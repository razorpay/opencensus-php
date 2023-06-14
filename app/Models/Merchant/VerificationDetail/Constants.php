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

    const ID                        = 'id';
    const STATUS                    = 'status';
    const CATEGORY_RESULT           = 'category_result';
    const WEBSITE_CATEGORISATION    = 'website_categorisation';
    const CONFIDENCE_SCORE          = 'confidence_score';
    const CATEGORY                  = 'category';
    const SUBCATEGORY               = 'subcategory';
    const PREDICTED_MCC             = 'predicted_mcc';

    const SIGNATORY_ALLOWED_ARTEFACTS = [
        Constant::CIN . '-' . ValidationConstants::IDENTIFIER,
        Constant::GSTIN . '-' . ValidationConstants::IDENTIFIER,
        Constant::LLP_DEED . '-' . ValidationConstants::IDENTIFIER,
        Constant::PARTNERSHIP_DEED . '-' . ValidationConstants::PROOF,
        Constant::SHOP_ESTABLISHMENT . '-' . ValidationConstants::IDENTIFIER,
        Constant::MSME . '-' . ValidationConstants::PROOF,
    ];

}
