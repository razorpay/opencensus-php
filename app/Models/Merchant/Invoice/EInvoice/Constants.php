<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

final class Constants
{
    const ACCESS_TOKEN = 'access_token';

    const USER_GSTIN = 'user_gstin';

    const TRANSACTION_DETAILS = 'transaction_details';
    const SUPPLY_TYPE = 'supply_type';
    const B2B = 'B2B';

    const DOCUMENT_DETAILS = 'document_details';
    const DOCUMENT_NUMBER  = 'document_number';
    const DOCUMENT_DATE = 'document_date';
    const DOCUMENT_TYPE = 'document_type';

    const SELLER_DETAILS = 'seller_details';
    const GSTIN = 'gstin';
    const LEGAL_NAME = 'legal_name';
    const ADDRESS_1 = 'address1';
    const ADDRESS_2 = 'address2';
    const LOCATION = 'location';
    const PINCODE = 'pincode';
    const STATE_CODE = 'state_code';

    const BUYER_DETAILS = 'buyer_details';
    const PLACE_OF_SUPPLY = 'place_of_supply';

    const VALUE_DETAILS = 'value_details';
    const TOTAL_IGST_VALUE = 'total_igst_value';
    const TOTAL_CGST_VALUE = 'total_cgst_value';
    const TOTAL_SGST_VALUE = 'total_sgst_value';
    const TOTAL_INVOICE_VALUE = 'total_invoice_value';
    const TOTAL_ASSESSABLE_VALUE = 'total_assessable_value';

    const ITEM_LIST = 'item_list';
    const ITEM_SERIAL_NUMBER = 'item_serial_number';
    const PRODUCT_DESCRIPTION   = 'product_description';
    const IS_SERVICE = 'is_service';
    const HSN_CODE = 'hsn_code';
    const UNIT = 'unit';
    const QUANTITY = 'quantity';
    const UNIT_PRICE = 'unit_price';
    const TOTAL_AMOUNT = 'total_amount';
    const ASSESSABLE_VALUE = 'assessable_value';
    const GST_RATE = 'gst_rate';
    const IGST_AMOUNT = 'igst_amount';
    const CGST_AMOUNT = 'cgst_amount';
    const SGST_AMOUNT = 'sgst_amount';
    const TOTAL_ITEM_VALUE = 'total_item_value';

    const RZP_GSTIN = '29AAGCR4375J1ZU';
    const RZP_LEGAL_NAME = 'Razorpay Software Private Limited';
    const RZP_ADDRESS = 'First Floor SJR Cyber 22 laskar hosur road Adugodi';
    const RZP_LOCATION = 'Bangalore';
    const RZP_PINCODE = 560030;
    const RZP_STATE_CODE = "29";

    const RSPL = 'rspl';
    const RZPL = 'rzpl';

    const CHANNEL = 'channel';
    const ACCOUNT_TYPE = 'account_type';

    const SELLER_ENTITY_DETAILS = [
        self::RSPL       =>   [
                            self::GSTIN         =>  '29AAGCR4375J1ZU',
                            self::LEGAL_NAME    =>  'Razorpay Software Private Limited',
                            self::ADDRESS_1     =>  'First Floor SJR Cyber 22 laskar hosur road Adugodi',
                            self::LOCATION      =>  'Bangalore',
                            self::PINCODE       =>   560030,
                            self::STATE_CODE    =>  '29',
        ],
        self::RZPL      =>   [
                            self::GSTIN         =>  '29AAKCR4702K1Z1',
                            self::LEGAL_NAME    =>  'RZPX PRIVATE LIMITED',
                            self::ADDRESS_1     =>  'First Floor SJR Cyber 22 laskar hosur road Adugodi',
                            self::LOCATION      =>  'Bangalore',
                            self::PINCODE       =>   560030,
                            self::STATE_CODE    =>  '29',
        ],
    ];

    const REFERENCE_DETAILS = 'reference_details';
    const INVOICE_REMARKS = 'invoice_remarks';
    const INVOICE_PERIOD_START_DATE = 'invoice_period_start_date';
    const INVOICE_PERIOD_END_DATE = 'invoice_period_end_date';
    const PRECEDING_DOCUMENT_DETAILS = 'preceding_document_details';
    const REFERENCE_OF_ORIGINAL_INVOICE = 'reference_of_original_invoice';
    const PRECEDING_INVOICE_DATE = 'preceding_invoice_date';
}
