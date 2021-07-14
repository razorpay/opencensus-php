<?php

namespace RZP\Models\Order\OrderMeta\TaxInvoice;

/**
 * Class Fields
 *
 * @package RZP\Models\Order\OrderMeta\TaxInvoice;
 */
class Fields
{
    const BUSINESS_GSTIN    = 'business_gstin';
    const INVOICE_NUMBER    = 'number';
    const INVOICE_DATE      = 'date';
    const CUSTOMER_NAME     = 'customer_name';
    const GST_AMOUNT        = 'gst_amount';
    const CESS_AMOUNT       = 'cess_amount';
    const CGST_AMOUNT       = 'cgst_amount';
    const SGST_AMOUNT       = 'sgst_amount';
    const IGST_AMOUNT       = 'igst_amount';
    const SUPPLY_TYPE       = 'supply_type';

    protected static $mandatoryGstFields = [
        self::BUSINESS_GSTIN,
        self::CUSTOMER_NAME,
        self::INVOICE_NUMBER,
    ];

    /**
     * @return string[]
     * List of parameters required for GST flow.
     * if not set, GST flow is silently ignored, no order_meta is created and normal order flow is resumed.
     */
    public static function getMandatoryGstFields()
    {
        return self::$mandatoryGstFields;
    }
}

