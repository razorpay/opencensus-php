<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Models\Merchant\Document\Type;

final class Constant
{
    // Emerchantpay APM Request Constants
    const REGISTRATION_NUMBER = 'registration_number';
    const GST_NUMBER = 'gst_number';
    const GOODS_TYPE_DESC = 'Please describe the products/services offered on your website';
    const PHYSICAL_DELIVERY_DESC = 'Are your product/service with physical delivery?';
    const AVG_DELIVERY_DAYS_DESC = 'Average delivery timeframe (in days)';

    const APM_REQUEST_NAMESPACE = 'merchant_international_onboard_reminder';
    const APM_REQUEST_ENTITY = 'emerchantpay_apm_request';

    const INTEGRATION_ENTITY_OPGSP_IMPORT = 'icici_opgsp_import';
    const HS_CODE = 'hs_code';

    const DOCUMENT_ID_SIGN = 'doc_';
    const FILE_ID_SIGN     = 'file_';

    public static $documentTypeMap = [
        'gst_certificate'       => Type::EMERCHANTPAY_GST_CERTIFICATE,
        'proof_of_ownership'    => Type::EMERCHANTPAY_PROOF_OF_OWNERSHIP,
        'aadhaar'               => Type::EMERCHANTPAY_AADHAAR,
        'pancard'               => Type::EMERCHANTPAY_PAN,
        'passport'              => Type::EMERCHANTPAY_PASSPORT,
    ];
}
