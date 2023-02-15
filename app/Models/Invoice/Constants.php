<?php

namespace RZP\Models\Invoice;


final class Constants
{
    const REFERENCE_ID = 'reference_id';
    const REFERENCE_TYPE = 'reference_type';
    const MODE = 'mode';

    const PAYMENT_FLOW = 'payment_request';
    const REFUND_FLOW = 'refund_request';

    const INV = 'INV';
    const CRN = 'CRN';

    const REFERENCE_TYPE_TO_TYPE_MAP = [
        self::PAYMENT_FLOW => Type::DCC_INV,
        self::REFUND_FLOW => Type::DCC_CRN,
    ];

    const TYPE_TO_DOC_TYPE_MAP = [
        Type::DCC_INV => self::INV,
        Type::DCC_CRN => self::CRN,
    ];

    const RSPL = 'rspl';

    const ACCESS_TOKEN = 'access_token';
    const USER_GSTIN = 'user_gstin';

    const TRANSACTION_DETAILS = 'transaction_details';
    const SUPPLY_TYPE = 'supply_type';
    const EXPWOP = 'EXPWOP';

    const DOCUMENT_DETAILS = 'document_details';
    const DOCUMENT_TYPE = 'document_type';
    const DOCUMENT_NUMBER = 'document_number';
    const DOCUMENT_DATE = 'document_date';

    const SELLER_DETAILS = 'seller_details';
    const GSTIN = 'gstin';
    const LEGAL_NAME = 'legal_name';
    const ADDRESS_1 = 'address1';
    const LOCATION = 'location';
    const PINCODE = 'pincode';
    const STATE_CODE = 'state_code';
    const SELLER_ENTITY_DETAILS = [
        self::GSTIN         =>  '29AAGCR4375J1ZU',
        self::LEGAL_NAME    =>  'Razorpay Software Private Limited',
        self::ADDRESS_1     =>  'First Floor SJR Cyber 22 laskar hosur road Adugodi',
        self::LOCATION      =>  'Bangalore',
        self::PINCODE       =>   560030,
        self::STATE_CODE    =>  '29',
    ];

    const BUYER_DETAILS = 'buyer_details';
    const UNREGISTERED_PERSON = 'URP';
    const PLACE_OF_SUPPLY = 'place_of_supply';
    const OUT_OF_INDIA = '96';

    const VALUE_DETAILS = "value_details";
    const TOTAL_ASSESSABLE_VALUE = "total_assessable_value";
    const TOTAL_INVOICE_VALUE = "total_invoice_value";

    const ITEM_LIST = 'item_list';
    const ITEM_SERIAL_NUMBER = 'item_serial_number';
    const PRODUCT_DESCRIPTION   = 'product_description';
    const IS_SERVICE = 'is_service';
    const HSN_CODE = 'hsn_code';
    const UNIT_PRICE = 'unit_price';
    const TOTAL_AMOUNT = 'total_amount';
    const ASSESSABLE_VALUE = 'assessable_value';
    const TOTAL_ITEM_VALUE = 'total_item_value';
    const SERIAL_NUMBER = '1';
    const DESCRIPTION = 'Service fee in relation to Dynamic Currency Conversion services';
    const SERVICE = 'Y';
    const ITEM_CODE = '997119';

    const IRN = 'Irn';
    const QR_CODE_URL = 'QRCodeUrl';

    const INVOICE_NUMBER_ISSUE_DATE = 'InvoiceNumberIssueDate';
    const INVOICE_NUMBER = 'InvoiceNumber';

    const DCC_E_INVOICE = 'dcc-e-invoice';

    // Error codes for DCC e-invoice
    const BUILDING_REQUEST_DATA_FAILED  = 'BUILDING_REQUEST_DATA_FAILED';
    const INVOICE_REGISTRATION_FAILED = 'INVOICE_REGISTRATION_FAILED';
    const INVOICE_PDF_GENERATION_FAILED = 'INVOICE_PDF_GENERATION_FAILED';
    const INVOICE_UPLOAD_FAILED = 'INVOICE_UPLOAD_FAILED';
    const INVOICE_CREATION_FAILED = 'INVOICE_CREATION_FAILED';

    const OPGSP_INVOICE_NUMBER = 'invoice_number';
    const INVOICE_NUMBER_LENGTH = 40;
    const MUTEX_MERCHANT_PAYMENT_DOCUMENT_UPLOAD_PREFIX = 'merchant_payment_document_upload_';
}
