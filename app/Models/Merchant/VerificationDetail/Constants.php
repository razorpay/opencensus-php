<?php


namespace RZP\Models\Merchant\VerificationDetail;


class Constants
{
    // Artefact Types
    const SHOP_ESTABLISHMENT = 'shop_establishment';
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

}
