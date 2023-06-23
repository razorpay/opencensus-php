<?php


namespace RZP\Models\Merchant\Detail\Upload;


class Constants
{
    // creating merchant by import data
    const FORMAT            = 'format';
    const FILE              = 'file';

    const AXIS_MIQ          = 'axis_miq';

    const BULK_UPLOAD_MIQ   = 'bulk_upload_miq';

    const SUPPORTED_FORMATS = [
        self::AXIS_MIQ
    ];

    /*
     * Constants for Axis MIQ upload headers
     */
    const MERCHANT_LEGAL_NAME   = "Merchant Legal Name";
    const MERCHANT_DBA_NAME     = "Merchant DBA Name";
    const LIVE_URL              = "Live URL";
    const CONTACT_NAME          = "Contact Name";
    const CONTACT_EMAIL         = "Authorized Email Address";
    const PHONE_NUMBER          = "Phone Number";
    const ADDRESS               = "Address";
    const CITY                  = "City";
    const STATE                 = 'State';
    const PIN_CODE              = "PIN Code";
    const ENTITY_TYPE           = "Entity Type ( Public / Partnership etc.)";
    const PAN_NUMBER            = "PAN number";
    const GST_NUMBER            = "GST Number";
    const CATEGORY              = "Category / Segment";
    const SUBCATEGORY           = "SubCategory";
    const PRODUCT_DESCRIPTION   = "Product Description";
    const MIN_TRANSACTION_VALUE = "Minimum Transaction Value";
    const MAX_TRANSACTION_VALUE = "Maximum Transaction Value";
    const BANK_ACC_NUMBER       = "Bank Account Number";
    const BANK_ACC_NAME         = "Bank Account Name";
    const IFSC_CODE             = "IFSC Code";
    const MCC_CODE              = 'MCC Code';

    /**
     * Constants for pricing automation MIQ.
     */
    const FEE_TYPE_PERCENT            = 'percent';
    const FEE_TYPE_FLAT               = 'flat';
    const FEE_TYPE_NA                 = 'na';
    const PRICING_FEE_BEARER          = 'pricing_fee_bearer';
    const PRICING_AMOUNT_RANGE_ACTIVE = 'pricing_amount_range_active';
    const PRICING_AMOUNT_RANGES       = 'pricing_amount_ranges';
    const PRICING_AMOUNT_RANGE_MIN    = 'pricing_amount_range_min';
    const PRICING_AMOUNT_RANGE_MAX    = 'pricing_amount_range_max';
    const PRICING_METHOD_TYPE         = 'pricing_method_type';
    const PRICING_NETWORK             = 'pricing_network';
    const PRICING_METHOD_SUBTYPE      = 'pricing_method_subtype';
}
