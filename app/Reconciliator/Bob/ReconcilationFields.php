<?php

namespace RZP\Reconciliator\Bob;

class ReconcilationFields
{
    const TRANSACTION_DATE         = 'Transaction Date';

    const TRANSACTION_TIME         = 'Transaction Time';

    const SETTLEMENT_DATE          = 'Settlement Date';

    const MERCHANT_TYPE            = 'Merchant Type';

    const TRANSACTION_SOURCE       =  'Transaction Source';

    const MERCHANT_AGGREGATOR_ID = 'Merchant Aggregator ID';

    const MID                      = 'MID';

    const MERCHANT_LEGAL_NAME      = 'Merchant Legal Name';

    const SID                      =  'SID';

    const STORE_TRADING            = 'Store Trading';

    const TID                      = 'TID';

    const BATCH_NUMBER             = 'Batch Number';

    const CARD_NUMBER              = 'Card Number';

    const DELIVERY_CHANNEL         = 'Delivery Channel';

    const PAYMENT_METHOD           = 'Payment Method';

    const ONUS_INDICATOR           = 'Onus Indicator';

    const INTERCHANGE              = 'Interchange';

    const INTERCHANGE_CATEGORY     = 'Interchange Category';

    //Determines if its domestic/international
    const DESTINATION              = 'Destination';

    //Card Type
    const CARD_TYPE                = 'Card Type';

    const PAYMENT_AGGREGATOR_ID    = 'Merchant Aggregator ID';

    const ISSUER_BANK              = 'Issuer Bank';

    const MERCHANT_CATEGORY_CODE   = 'Merchant Category Code';

    const MCC_CATEGORY             = 'MCC Category';

    const TRANSACTION_CATEGORY     = 'Transaction Category';

    const TRANSACTION_TYPE         = 'Transaction Type';

    const TRANSACTION_CURRENCY_CODE = 'Transaction Currency Code';

    const TRANSACTION_AMOUNT        = 'Transaction Amount';

    const ADDITIONAL_AMOUNT         = 'Additional Amount';

    const SETTLEMENT_AMOUNT = 'Settlement Currency';

    const LATE_SETTLEMENT_FEE_AMOUNT = 'Late Settlement Fee Amount';

    const RRF_AMOUNT                 = 'RRF Amount';

    const MSF_AMOUNT                 = 'MSF Amount';

    const GST                        = 'GST';

    const MIN_AMOUNT                 = 'Min Amount';

    const MAX_AMOUNT                 = 'Max Amount';

    const CSF_AMOUNT                 = 'CSF Amount';

    const CSF_TAX                    = 'CSF Tax';

    const NET_AMOUNT                 = 'Net Amount';

    const APPROVED_INDICATOR         = 'Approved/Declined Indicator';

    const INVOICE_NUMBER             = 'Invoice Number';

    const AUTH_CODE                  = 'Auth/Approval Code';

    const RRN                        = 'Retrieval Reference Number';

    const TRACE_NO                   = 'System Trace Audit Number';

    const PG_PAYMENT_TRANSACTION_ID  = 'PG Payment Transaction ID';

    const PG_TRANSACTION_ID          = 'PG Transaction ID';

    const MERCHANT_TRACK_ID          = 'Merchant Track ID';

    const HOST_TRANSACTION_ID        = 'Host Transaction ID';

    const UDF_1                      = 'UDF 1';

    const UDF_2                      = 'UDF 2';

    const UDF_3                      = 'UDF 3';

    const UDF_4                      = 'UDF 4';

    const UDF_5                      = 'UDF 5';

    const UDF_6                      =  'UDF 6';

    const UDF_7                      = 'UDF 7';

    const UDF_8                      = 'UDF 8';

    const UDF_9                      = 'UDF 9';

    const UDF_10                     = 'UDF 10';

    const UDF_11                     = 'UDF 11';

    const UDF_12                     = 'UDF 12';

    const UDF_13                     = 'UDF 13';

    const UDF_14                     = 'UDF 14';

    const UDF_15                     = 'UDF 15';

    const UDF_16                     = 'UDF 16';

    //The date actually on which payment got settled
    const PAYMENT_DATE               = 'Payment Date';

    const TRANSACTION_STATUS         = 'Transaction Status';
}
