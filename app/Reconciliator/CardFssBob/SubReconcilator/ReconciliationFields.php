<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

class ReconciliationFields
{
    const TRANSACTION_DATE           = 'transaction_date';

    const TRANSACTION_TIME           = 'transaction_time';

    const SETTLEMENT_DATE            = 'settlement_date';

    const MERCHANT_TYPE              = 'transaction_source';

    const TRANSACTION_SOURCE         = 'transaction_id';

    const MERCHANT_AGGREGATOR_ID     = 'merchant_aggregator_id';

    const MID                        = 'mid';

    const MERCHANT_LEGAL_NAME        = 'merchant_legal_name';

    const SID                        = 'sid';

    const STORE_TRADING              = 'store_trading';

    const TID                        = 'tid';

    const BATCH_NUMBER               = 'batch_number';

    const CARD_NUMBER                = 'card_number';

    const DELIVERY_CHANNEL           = 'delivery_channel';

    const PAYMENT_METHOD             = 'payment_method';

    const ONUS_INDICATOR             = 'onus_indicator';

    const INTERCHANGE                = 'Interchange';

    const INTERCHANGE_CATEGORY       = 'Interchange Category';

    // Determines if its domestic/international
    const DESTINATION                = 'destination';

    // Card Type
    const CARD_TYPE                  = 'card_type';

    const PAYMENT_AGGREGATOR_ID      = 'merchant_aggregator_id';

    const ISSUER_BANK                = 'issuer_bank';

    const MERCHANT_CATEGORY_CODE     = 'merchant_category_code';

    const MCC_CATEGORY               = 'mcc_category';

    const TRANSACTION_CATEGORY       = 'transaction_category';

    const TRANSACTION_TYPE           = 'transaction_type';

    const TRANSACTION_CURRENCY_CODE  = 'transaction_currency_code';

    const TRANSACTION_AMOUNT         = 'transaction_amount';

    const ADDITIONAL_AMOUNT          = 'additional_amount';

    const SETTLEMENT_AMOUNT          = 'settlement_currency';

    const LATE_SETTLEMENT_FEE_AMOUNT = 'late_settlement_fee_amount';

    const RRF_AMOUNT                 = 'rrf_amount';

    const MSF_AMOUNT                 = 'msf_amount';

    const GST                        = 'gst';

    const MIN_AMOUNT                 = 'min_amount';

    const MAX_AMOUNT                 = 'max_amount';

    const CSF_AMOUNT                 = 'csf_amount';

    const CSF_TAX                    = 'csf_tax';

    const NET_AMOUNT                 = 'net_amount';

    const APPROVED_INDICATOR         = 'approveddeclined_indicator';

    const INVOICE_NUMBER             = 'invoice_number';

    const AUTH_CODE                  = 'authapproval_code';

    const RRN                        = 'retrieval_reference_number';

    const TRACE_NO                   = 'trace_no';

    const PG_PAYMENT_TRANSACTION_ID  = 'pg_payment_transaction_id';

    const PG_TRANSACTION_ID          = 'pg_transaction_id';

    const MERCHANT_TRACK_ID          = 'merchant_track_id';

    const HOST_TRANSACTION_ID        = 'host_transaction_id';


    // The date actually on which payment got settled
    const PAYMENT_DATE               = 'payment_date';

    const TRANSACTION_STATUS         = 'transaction_status';
}
