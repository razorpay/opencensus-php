<?php

namespace RZP\Models\Payment\Refund;

class Constants
{
    const META                                 = 'meta';
    const MODE                                 = 'mode';
    const CODE                                 = 'code';
    const IS_FTA                               = 'is_fta';
    const IS_DCC                               = 'is_dcc';
    const MOZART                               = 'mozart';
    const MESSAGE                              = 'message';
    const ENTITIES                             = 'entities';
    const REFUND_IDS                           = 'refund_ids';
    const PAYMENT_IDS                          = 'payment_ids';
    const DB_FETCH_LIMIT                       = 'limit';
    const GATEWAY_ENTITY                       = 'gateway_entity';
    const PUBLIC_ENTITIES                      = 'public_entities';
    const CUSTOM_PUBLIC_ENTITIES               = 'custom_public_entities';
    const SPEED_CHANGE_TIME                    = 'speed_change_time';
    const REFUND_REFERENCE1                    = 'refund_reference1';
    const INSTANT_REFUND_SUPPORT               = 'instant_refund_support';
    const GATEWAY_REFUND_SUPPORT               = 'gateway_refund_support';
    const DIRECT_SETTLEMENT_REFUND             = 'direct_settlement_refund';
    const MAX_REFUND_RETRY_ATTEMPTS            = 3;
    const MAX_REFUND_VERIFY_REQUESTS           = 100;
    const PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND = 'payment_age_limit_for_gateway_refund';
    const IS_UPI_AND_AMOUNT_MISMATCHED         = 'is_upi_and_amount_mismatched';
    const IS_HDFC_VAS_DS_CUSTOMER_FEE_BEARER   = 'is_hdfc_vas_ds_customer_fee_bearer_surcharge';
    const DISCOUNT_RATIO                       = 'discount_ratio';
    const DISCOUNTED_AMOUNT                    = 'discounted_amount';
    const GATEWAY_AMOUNT                       = 'gateway_amount';
    const MIN_CURRENCY_AMOUNT                  = 'min_currency_amount';
    const REFUND_GATEWAY                       = 'refund_gateway';
    const UNDISPUTED_PAYMENT                   = 'undisputed_payment';
    const REFUND_AUTHORIZED_PAYMENT            = 'refund_authorized_payment';

    /**
     * We get the last 10 days refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT      = 864000;
    /**
     * Refund public status related constants
     */
    const REFUND_PUBLIC_STATUS_FEATURE_ENABLED             = 'refund_public_status_feature_enabled';
    const REFUND_PENDING_STATUS_FEATURE_ENABLED            = 'refund_pending_status_feature_enabled';
    /**
     * Transaction tracker related constants
     */
    const ID                       = 'id';
    const DAYS                     = 'days';
    const RZP_ID                   = 'rzp_id';
    const REFUND                   = 'refund';
    const PAYMENT                  = 'payment';
    const ID_TYPE                  = 'id_type';
    const UNKNOWN                  = 'unknown';
    const REFUNDS                  = 'refunds';
    const NPCI_RRN                 = 'npci_rrn';
    const PAYMENTS                 = 'payments';
    const ORDER_ID                 = 'order_id';
    const LATE_AUTH                = 'late_auth';
    const REFUND_ID                = 'refund_id';
    const PAYMENT_ID               = 'payment_id';
    const ROUTE_NAME               = 'route_name';
    const FAILED_AGED              = 'failed_aged';
    const MERCHANT_ID              = 'merchant_id';
    const MERCHANT_NAME            = 'merchant_name';
    const PRIMARY_MESSAGE          = 'primary_message';
    const TERTIARY_MESSAGE         = 'tertiary_message';
    const SECONDARY_MESSAGE        = 'secondary_message';
    const MERCHANT_REFERENCE       = 'merchant_reference';
    const PAYMENT_CREATED_AT       = 'payment_created_at';
    const MERCHANT_ACTIVATED_AT    = 'merchant_activated_at';
    const BUSINESS_SUPPORT_DETAILS = 'business_support_details';
    const CURRENCY                 = 'currency';
    const ACQUIRER_DATA            = 'acquirer_data';
    const FUNDS_ON_HOLD            = 'funds_on_hold';
    const INPUT                    = 'input';

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';
    const RESPONSE_DATA          = 'data';

    const BIN             = 'bin';
    const VPA             = 'vpa';
    const AMOUNT          = 'amount';
    const ISSUER          = 'issuer';
    const METHOD          = 'method';
    const VPA_ADDRESS     = 'address';
    const CARD_TYPE       = 'card_type';
    const SOURCE_VPA      = 'source_vpa';
    const BANK_ACCOUNT    = 'bank_account';
    const NETWORK_CODE    = 'network_code';
    const CARD_TRANSFER   = 'card_transfer';
    const TRANSFER_METHOD = 'transfer_method';


    const TOKEN_STATUS              = 'token_status';
    const TOKENIZED                 = 'tokenized';
    const TOKEN_EXPIRED_AT          = 'token_expired_at';
    const IIN                       = 'iin';
    const INTERNATIONAL             = 'international';

    /**
     * razorx experiments related constants
     */
    const RAZORX_VARIANT_ON                                      = 'on';
    const RAZORX_VARIANT_OFF                                     = 'off';
    const RAZORX_KEY_REFUND_ROUTE_VIA_FTA_SUFFIX                 = 'refund_route_via_fta';
    const RAZORX_KEY_REFUND_FETCH_MULTIPLE_FROM_SCROOGE          = 'refund_fetch_multiple_from_scrooge';
    const RAZORX_KEY_TERMINAL_REFUNDS_ROUTE_VIA_FTA_SUFFIX       = 'terminal_refunds_route_via_fta';
    const RAZORX_KEY_SKIP_PAYMENT_ENTITY_UPDATE_FOR_REVERSAL     = 'skip_payment_entity_update_for_reversal';

    // Experiment to set up FTA status update flow
    const REFUNDS_0_LOC_FTA_STATUS_UPDATE_FLOW_RAMP_UP = 'refunds_0_loc_fta_status_update_flow_ramp_up';
    // Setting cache key ttl for 2 minutes since it's sync flow
    const RAMP_UP_KEY_TTL = 120;

    // Used to ack scrooge that update status request came from fta status update
    const FTA_UPDATE = 'fta_update';

    const FT_UNKNOWN = 'FT_UNKNOWN';

    /**
     * Scrooge File Based Refunds request related constants
     */
    const SCROOGE_GT               = 'gt';
    const SCROOGE_ID               = 'id';
    const SCROOGE_GTE              = 'gte';
    const SCROOGE_LTE              = 'lte';
    const SCROOGE_BANK             = 'bank';
    const SCROOGE_SKIP             = 'skip';
    const SCROOGE_COUNT            = 'count';
    const SCROOGE_QUERY            = 'query';
    const SCROOGE_METHOD           = 'method';
    const SCROOGE_REFUNDS          = 'refunds';
    const SCROOGE_GATEWAY          = 'gateway';
    const SCROOGE_CREATED_AT       = 'created_at';
    const SCROOGE_BASE_AMOUNT      = 'base_amount';
    const SCROOGE_GATEWAY_ACQUIRER = 'gateway_acquirer';
    const SCROOGE_PROCESSED_SOURCE = 'processed_source';
    const SCROOGE_PAYMENT_GATEWAY_CAPTURED = 'payment_gateway_captured';

    // Fetch Entities Related Constants
    const EXTRA_DATA          = 'extra_data';
    const SCROOGE_MERCHANT_ID = 'merchant_id';

    const DISPATCH_BATCH_SIZE = 5;
    const DISPATCH_DELAY_TIME = 'dispatch_delay_time';

    // Dashboard related constants
    // Some constants are named not make complete sense,
    // basically to avoid user understanding the feature when inspected on dashboard
    const FAILED_AT            = 'failed_at';
    const REFUND_STATUS_FILTER = 'rs_filter';

    // Skip refund verify flag
    const SKIP_REFUND_VERIFY = 'skip_refund_verify';

    // Batch related constants
    const NOTES                 = 'notes';
    const STATUS                = 'status';
    const REFUNDED_AMOUNT       = 'refunded_amount';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const FAILURE               = 'failure';
    const SPEED                 = 'speed';
    const IFSC                  = 'ifsc';
    const BENEFICIARY_NAME      = 'beneficiary_name';
    const ACCOUNT_NUMBER        = 'account_number';
    const TRANSFER_MODE         = 'transfer_mode';

    // FE refund creation data
    const FEES                          = 'fees';
    const VALUE                         = 'value';
    const IR_OPTION                     = 'option';
    const MESSAGE_REASON                = 'reason';
    const MESSAGES                      = 'messages';
    const IR_OPTION_ENABLED             = 'enabled';
    const IR_OPTION_DISABLED            = 'disabled';
    const REVERSE_ALL                   = 'reverse_all';
    const IR_OPTION_ONLY_OPTIMUM        = 'onlyOptimum';
    const IR_OPTION_DEFAULT_OPTIMUM     = 'defaultOptimum';
    const INSTANT_REFUND                = 'instant_refund';
    const IS_REFUND_ALLOWED             = 'is_refund_allowed';
    const IS_TRANSFERS_REVERSAL_ALLOWED = 'is_transfers_reversal_allowed';

    const MESSAGE_KEY_INSUFFICIENT_FUNDS       = 'INSUFFICIENT_FUNDS';
    const MESSAGE_KEY_REFUNDS_ON_AGED_PAYMENTS = 'REFUNDS_ON_AGED_PAYMENTS';
    const MESSAGE_KEY_IR_SUPPORTED_INSTRUMENTS = 'IR_SUPPORTED_INSTRUMENTS';

    const MESSAGE_REASON_INSUFFICIENT_FUNDS       = 'Your account does not have sufficient balance to refund this payment.';
    const MESSAGE_REASON_IR_INSUFFICIENT_FUNDS    = 'Your account does not have sufficient balance to instantly refund this payment.';
    const MESSAGE_REASON_IR_SUPPORTED_INSTRUMENTS = 'Currently, Instant Refunds are available on TPV, netbanking, UPI and select credit cards and debit cards.';

    // For flipkart like cases we show refunds as processed after 48 hours
    // even if it is not actually processed and config is stored in scrooge
    // Todo : Fetch such public status values/configs from scrooge
    const SCROOGE_PUBLIC_STATUS_TO_PROCESSED_TIME = 172800;

    const SPEED_COUNT                    = 'speed_count';
    const DEFAULT                        = 'default';
    const NORMAL                         = 'normal';
    const OPTIMUM                        = 'optimum';
    // Default refund amount value set for mode decisioning when actual refund amount is unknown
    // Set tentatively to 100 rupees since no modes are restricted for this amount
    const DEFAULT_REFUND_AMOUNT_FOR_MODE_DECISIONING = 10000;

    const DATA     = 'data';
    const ERROR    = 'error';
    const FTA_DATA = 'fta_data';

    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';

    // sets type of refund. Auto/manual/Merchant Initiated etc..
    const REFUND_TYPE = 'refund_type';

    // fetch entities v2 related constants
    const FAILURE_COUNT            = 'failure_count';
    const SUCCESS_COUNT            = 'success_count';
    const REQUEST_COUNT            = 'request_count';
    const NO_DATA_FOUND            = 'NO_DATA_FOUND';
    const PAYMENT_NOT_FOUND        = 'PAYMENT_NOT_FOUND';
    const SKIPPED_PAYMENT_IDS      = 'skipped_payment_ids';
    const SKIPPED_FETCH_ENTITIES   = 'skipped_fetch_entities';
    const FETCH_ENTITIES_ERROR     = 'FETCH_ENTITIES_ERROR';
    const AMOUNT_UNREFUNDED        = 'amount_unrefunded';
    const BASE_AMOUNT_UNREFUNDED   = 'base_amount_unrefunded';
    const IS_UPI_OTM               = 'is_upi_otm';
    const CURRENCY_CONVERSION_RATE = 'currency_conversion_rate';
    const PAYMENT_RAW_AMOUNT       = 'payment_raw_amount';
    const PAYMENT_RAW_CURRENCY     = 'payment_raw_currency';


    // to revert payment attributes on transaction create failure
    const COMPENSATE_PAYMENT = 'compensate_payment';
    const TRANSACTION_ID     = 'transaction_id';

    const CREATED_AT = 'created_at';

    const PG_LEDGER_REVERSE_SHADOW                  = 'pg_ledger_reverse_shadow';
    const JOURNAL_ID                                = 'journal_id';
    const FEE_ONLY_REVERSAL                         = 'fee_only_reversal';

    // Upi Airtel Refund File
    const ORG_RRN                 = 'Org_RRN';
    const DATE_AND_TIME           = 'Date_and_Time';
    const BANK_ORG_TRANSACTION_ID = 'Bank_Org_Transaction_Id';
    const ORG_AMOUNT              = 'Org_Amount';
    const REFUND_AMOUNT           = 'Refund_Amount';
    const REFUND_STATUS           = 'Refund Status';
    const REFUND_REASON           = 'Refund Reason';

    // Upi Yesbank Refund File
    const BANK_REF                = 'Bankadjref';
    const FLAG                    = 'flag';
    const DATE                    = 'shtdat';
    const AMT                     = 'adjamt';
    const SHSER                   = 'shser';
    const UTXID                   = 'UTXID';
    const FILENAME                = 'filename';
    const REASON                  = 'Reason';
    const SPECIFY_OTHER           = 'specifyother';

    // Currency Denomination Keys
    const PAYMENT_RAW_CURRENCY_DENOMINATION     = 'payment_raw_currency_denomination';
    const GATEWAY_CURRENCY_DENOMINATION         = 'gateway_currency_denomination';
}
