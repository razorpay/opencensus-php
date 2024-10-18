<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

class Constants
{
    const FILE_BATCH_SIZE = 20000;
    const PAYMENT_BATCH_SIZE = 30000;
    const INVOICE_BATCH_SIZE = 20000;

    const INVOICE_ZIP_BATCH_SIZE = 2500;

    const OPGSP_INVOICE_ZIP_TYPE = 'opgsp_invoice_zip';

    const REQUEST_ACTION_PAYMENT = 'capture';
    const REQUEST_ACTION_REFUND = 'refund';
    const REQUEST_ACTION_CHARGEBACK = 'chargeback';
    const REQUEST_ACTION_CHARGEBACK_REVERSAL = 'chargeback_reversal';
    const CONSOLIDATED_SHEET_FILE_NAME = 'Consolidated';
    const TRANSACTIONAL_SHEET_FILE_NAME = 'Transactional';

    const DISPUTE = 'dispute';

    const FIELDSTODECRYPT = ['name', 'line1', 'line2', 'city', 'state', 'country', 'zipcode'];

    const  SLACK_CHANNEL = 'tech-cross-border-alerts';
    const SUCCESS = 'Success';
    const FAILED = 'Failed';
    const BAD_REQUEST_PAYMENT_NOTES_MISSING                                          = 'BAD_REQUEST_PAYMENT_NOTES_MISSING';
    const BAD_REQUEST_PAYMENT_INVOICE_NUMBER_NOT_FOUND                              = 'BAD_REQUEST_PAYMENT_INVOICE_NUMBER_NOT_FOUND';
    const  BAD_REQUEST_PAYMENT_INVOICE_LENGTH_NOT_VALID                              ='BAD_REQUEST_PAYMENT_INVOICE_LENGTH_NOT_VALID';
    const BAD_REQUEST_PAYMENT_ALREADY_EXIST_WITH_SAME_INVOICE_NUMBER                = 'BAD_REQUEST_PAYMENT_ALREADY_EXIST_WITH_SAME_INVOICE_NUMBER';
    const BAD_REQUEST_PAYMENT_CUSTOMER_ID_NOT_FOUND                                  = 'BAD_REQUEST_PAYMENT_CUSTOMER_ID_NOT_FOUND';
    const BAD_REQUEST_PAYMENT_ORDER_CUSTOMER_SHIPPING_ADDRESS_NOT_FOUND              = 'BAD_REQUEST_PAYMENT_ORDER_CUSTOMER_SHIPPING_ADDRESS_NOT_FOUND';
    const BAD_REQUEST_INVALID_PURPOSE_CODE                                          = 'BAD_REQUEST_INVALID_PURPOSE_CODE';
    const BAD_REQUEST_INVALID_HS_CODE                                               = 'BAD_REQUEST_INVALID_HS_CODE';
    const LRS_ORDER_WITHOUT_TPV                                                     = 'LRS_ORDER_WITHOUT_TPV';
}
