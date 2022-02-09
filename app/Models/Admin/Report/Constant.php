<?php

namespace RZP\Models\Admin\Report;

use RZP\Models\Payment;

class Constant
{
    // Merchant Properties
    const FIELD_PARTNER_ID = 'partner_id';
    const FIELD_MERCHANT_ID = 'merchant_id'; //
    const FIELD_MERCHANT_NAME = 'merchant_name';
    const FIELD_MERCHANT_CATEGORY = 'merchant_category';
    const FIELD_MERCHANT_SUB_CATEGORY = 'merchant_sub_category';
    const FIELD_MERCHANT_STATUS = 'merchant_status';

    // Payment Methods
    const FIELD_METHOD = 'method'; //
    const FIELD_PAYMENT_METHOD = 'payment_method';//
    const FIELD_CREDIT_CARD = 'credit_card';
    const FIELD_DEBIT_CARD = 'debit_card';
    const FIELD_NETBANKING = 'netbanking';
    const FIELD_UPI = 'upi';
    const FIELD_BQR = 'bqr';
    const FIELD_PAYZAPP = 'payzapp';
    const FIELD_WALLET              = 'wallet';

    const CARD                  = 'card';
    const NETBANKING            = 'netbanking';
    const WALLET                = 'wallet';
    const EMI                   = 'emi';
    const UPI                   = 'upi';
    const TRANSFER              = 'transfer';
    const BANK_TRANSFER         = 'bank_transfer';
    const AEPS                  = 'aeps';
    const EMANDATE              = 'emandate';
    const CARDLESS_EMI          = 'cardless_emi';
    const PAYLATER              = 'paylater';
    const NACH                  = 'nach';
    const APP                   = 'app';
    const COD                   = 'cod';
    const UNSELECTED            = 'unselected';

    // Amount and counts
    const FIELD_TOTAL_AMOUNT = 'total_amount';
    const FIELD_SUCCESS_AMOUNT = 'success_amount';
    const FIELD_FAILURE_AMOUNT = 'failure_amount';

    const FIELD_TOTAL_COUNT = 'total_count';
    const FIELD_SUCCESS_COUNT = 'success_count';
    const FIELD_FAILURE_COUNT = 'failure_count';

    const FIELD_PAYMENT_COUNT = 'payment_count';
    const FIELD_PAYMENT_AMOUNT = 'payment_amount';

    const FIELD_SUCCESS_RATE = 'success_rate';

    // Errors
    const FIELD_ERROR_CODE = 'error_code';
    const FIELD_ERROR_SOURCE = 'error_source';
    const FIELD_ERROR_STEP = 'error_step';
    const FIELD_ERROR_REASON = 'error_reason';
    const FIELD_ERROR_COUNT = 'error_count';

    // Other Fields
    const FIELD_RANK = 'rank';//
    const FIELD_DATE = 'date';

    const DEFAULTS             = 'defaults';
    const EXPAND               = 'expand';
    const EXPAND_EACH          = 'expand.*';
    const FROM                 = 'from';
    const TO                   = 'to';
    const COUNT                = 'count';
    const SKIP                 = 'skip';
    const DELETED              = 'deleted';

    const LABEL                = 'label';
    const TYPE                 = 'type';
    const VALUES               = 'values';

    const TYPE_STRING          = 'string';
    const TYPE_NUMBER          = 'number';
    const TYPE_BOOLEAN         = 'boolean';
    const TYPE_ARRAY           = 'array';
    const TYPE_OBJECT          = 'object';
    const TYPE_DATE            = 'date';

    const DOWNLOAD = 'download';

    ///////////////////  Report Types  ///////////////////
    const REPORT_TYPE_SUMMARY  = 'summary';
    const REPORT_TYPE_DETAIL   = 'detail';

    const REPORT_TYPE_SUMMARY_MERCHANT  = 'summary_merchant';

    const REPORT_TYPE_SUMMARY_PAYMENT   = 'summary_payment';

    const REPORT_TYPE_DETAILED_MERCHANT = 'detailed_merchant';
    const REPORT_TYPE_SINGLE_MERCHANT_DETAIL = 'single_merchant_detail_download';

    const REPORT_TYPE_DETAILED_TRANSACTION = 'detailed_transaction';

    const REPORT_TYPE_DETAILED_FAILURE = 'detailed_failure';
    const REPORT_TYPE_DETAILED_FAILURE_DETAIL = 'detailed_failure_detail';
    const REPORT_TYPE_DETAILED_FAILURE_DETAIL_DOWNLOAD = 'detailed_failure_detail_download';

    //////////////////////////////////////////////////////

    /// Fact file key name
    const MERCHANT_FACT_NAME = 'merchant_fact_name';
    const PAYMENT_FACT_NAME  = 'payment_fact_name';
    const DEFAULT = 'default';
}
