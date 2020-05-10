<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

class ReconciliationFields
{
    const TRANSACTION_DATE           = ['transaction_date', 'transactiondate'];

    const TRANSACTION_TIME           = ['transaction_time', 'transactiontime'];

    const SETTLEMENT_DATE            = ['settlement_date', 'settlementdate'];

    const MERCHANT_TYPE              = 'merchant_type';

    const TRANSACTION_SOURCE         = 'transaction_source';

    const TRANSACTION_ID             = ['transaction_id', 'transactionid'];

    const MERCHANT_AGGREGATOR_ID     = ['merchant_aggregator_id', 'merchantaggregatorid'];

    const MID                        = ['mid', 'merchantcode'];

    const MERCHANT_LEGAL_NAME        = ['merchant_legal_name', 'merchantlegalname'];

    const SID                        = ['sid', 'storecode'];

    const STORE_TRADING              = ['store_trading', 'storetradingname'];

    const TID                        = ['tid', 'terminalcode'];

    const BATCH_NUMBER               = 'batch_number';

    const CARD_NUMBER                = ['card_number', 'cardnumber'];

    const DELIVERY_CHANNEL           = 'delivery_channel';

    const PAYMENT_METHOD             = ['payment_method', 'paymentmethod'];

    const ONUS_INDICATOR             = ['onus_indicator', 'onusindicator'];

    const INTERCHANGE                = 'interchange';

    const INTERCHANGE_CATEGORY       = ['interchange_category', 'interchangecategory'];

    // Determines if its domestic/international
    const DESTINATION                = 'destination';

    // Card Type
    const CARD_TYPE                  = 'card_type';

    const PAYMENT_METHOD_AGGREGATOR  = 'payment_method_aggregator';

    const ISSUER_BANK                = 'issuer_bank';

    const MERCHANT_CATEGORY_CODE     = ['merchant_category_code', 'merchantcategorycode'];

    const MCC_CATEGORY               = 'mcc_category';

    const TRANSACTION_CATEGORY       = 'transaction_category';

    const TRANSACTION_TYPE           = ['transaction_type', 'transactiontype'];

    const TRANSACTION_CURRENCY_CODE  = ['transaction_currency_code', 'transactioncurrencycode', 'transaction_currency'];

    const TRANSACTION_AMOUNT         = ['transaction_amount', 'transactionamount'];

    const ADDITIONAL_AMOUNT          = ['additional_amount', 'additionalamount'];

    const SETTLEMENT_CURRENCY        = ['settlement_currency', 'settlementcurrency'];

    const SETTLEMENT_AMOUNT          = ['settlement_amount', 'settlementamount'];

    const LATE_SETTLEMENT_FEE_AMOUNT = ['late_settlement_fee_amount', 'latesettlementfeeamount'];

    const RRF_AMOUNT                 = ['rrf_amount', 'rrfamount'];

    const MSF_AMOUNT                 = ['msf_amount', 'msfamount'];

    const GST                        = 'gst';

    const MIN_AMOUNT                 = 'min_amount';

    const MAX_AMOUNT                 = 'max_amount';

    const CSF_AMOUNT                 = ['csf_amount', 'csfamount'];

    const CSF_TAX                    = 'csf_tax';

    const NET_AMOUNT                 = ['net_amount', 'netamount'];

    const APPROVED_INDICATOR         = ['approveddeclined_indicator', 'approvaldeclinedindicator'];

    const INVOICE_NUMBER             = ['invoice_number', 'invoicenumber'];

    const AUTH_CODE                  = ['authapproval_code', 'authapprovalcode'];

    const RRN                        = ['retrieval_reference_number', 'retrievalreferencenumber'];

    const ARN                        = 'arn';

    const TRACE_NO                   = ['system_trace_audit_number', 'systemtraceauditnumber'];

    const PG_PAYMENT_TRANSACTION_ID  = ['pg_payment_transaction_id', 'paymentgatewaypaymtranid'];

    const PG_TRANSACTION_ID          = ['pg_transaction_id', 'paymentgatewaytranid'];

    const MERCHANT_TRACK_ID          = ['merchant_track_id', 'merchanttrackid'];

    const HOST_TRANSACTION_ID        = 'host_transaction_id';

    // The date actually on which payment got settled
    const GATEWAY_SETTLED_DATE       = ['payment_date', 'merchantsettlementdate'];

    const TRANSACTION_STATUS         = 'transaction_status';

    //
    // This is the column order in 68 column fss_bob mis file
    // Note : As udf 16 is always blank, replacing it to have arn,
    // so that output file is still consistent with existing looker
    // schema of 68 columns.
    //
    const OLD_TO_NEW_FILE_MAPPING = [
        self::TRANSACTION_DATE,
        self::TRANSACTION_TIME,
        self::SETTLEMENT_DATE,
        self::MERCHANT_TYPE,
        self::TRANSACTION_SOURCE,
        self::TRANSACTION_ID,
        self::MERCHANT_AGGREGATOR_ID,
        self::MID,
        self::MERCHANT_LEGAL_NAME,
        self::SID,
        self::STORE_TRADING,
        self::TID,
        self::BATCH_NUMBER,
        self::CARD_NUMBER,
        self::DELIVERY_CHANNEL,
        self::PAYMENT_METHOD,
        self::ONUS_INDICATOR,
        self::INTERCHANGE,
        self::INTERCHANGE_CATEGORY,
        self::DESTINATION,
        self::CARD_TYPE,
        self::PAYMENT_METHOD_AGGREGATOR,
        self::ISSUER_BANK,
        self::MERCHANT_CATEGORY_CODE,
        self::MCC_CATEGORY,
        self::TRANSACTION_CATEGORY,
        self::TRANSACTION_TYPE,
        self::TRANSACTION_CURRENCY_CODE,
        self::TRANSACTION_AMOUNT,
        self::ADDITIONAL_AMOUNT,
        self::SETTLEMENT_CURRENCY,
        self::SETTLEMENT_AMOUNT,
        self::LATE_SETTLEMENT_FEE_AMOUNT,
        self::RRF_AMOUNT,
        self::MSF_AMOUNT,
        self::GST,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::CSF_AMOUNT,
        self::CSF_TAX,
        self::NET_AMOUNT,
        self::APPROVED_INDICATOR,
        self::INVOICE_NUMBER,
        self::AUTH_CODE,
        self::RRN,
        self::TRACE_NO,
        self::PG_PAYMENT_TRANSACTION_ID,
        self::PG_TRANSACTION_ID,
        self::MERCHANT_TRACK_ID,
        self::HOST_TRANSACTION_ID,
        'udf_1',
        'udf_2',
        'udf_3',
        'udf_4',
        'udf_5',
        'udf_6',
        'udf_7',
        'udf_8',
        'udf_9',
        'udf_10',
        'udf_11',
        'udf_12',
        'udf_13',
        'udf_14',
        'udf_15',
        self::ARN,
        self::GATEWAY_SETTLED_DATE,
        self::TRANSACTION_STATUS,
    ];
}
