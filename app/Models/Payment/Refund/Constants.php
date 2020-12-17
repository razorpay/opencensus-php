<?php

namespace RZP\Models\Payment\Refund;

class Constants
{
    const META                                 = 'meta';
    const MODE                                 = 'mode';
    const IS_FTA                               = 'is_fta';
    const MOZART                               = 'mozart';
    const ENTITIES                             = 'entities';
    const REFUND_IDS                           = 'refund_ids';
    const DB_FETCH_LIMIT                       = 'limit';
    const GATEWAY_ENTITY                       = 'gateway_entity';
    const SPEED_CHANGE_TIME                    = 'speed_change_time';
    const REFUND_REFERENCE1                    = 'refund_reference1';
    const INSTANT_REFUND_SUPPORT               = 'instant_refund_support';
    const GATEWAY_REFUND_SUPPORT               = 'gateway_refund_support';
    const DIRECT_SETTLEMENT_REFUND             = 'direct_settlement_refund';
    const MAX_REFUND_RETRY_ATTEMPTS            = 3;
    const MAX_REFUND_VERIFY_REQUESTS           = 100;
    const PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND = 'payment_age_limit_for_gateway_refund';
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
    const FAILED_AGED              = 'failed_aged';
    const MERCHANT_ID              = 'merchant_id';
    const MERCHANT_NAME            = 'merchant_name';
    const PRIMARY_MESSAGE          = 'primary_message';
    const TERTIARY_MESSAGE         = 'tertiary_message';
    const SECONDARY_MESSAGE        = 'secondary_message';
    const MERCHANT_REFERENCE       = 'merchant_reference';
    const PAYMENT_CREATED_AT       = 'payment_created_at';
    const BUSINESS_SUPPORT_DETAILS = 'business_support_details';

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

    /**
     * razorx experiments related constants
     */
    const RAZORX_VARIANT_ON                      = 'on';
    const RAZORX_KEY_REFUND_ROUTE_VIA_FTA_SUFFIX = 'refund_route_via_fta';

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

    const ERROR    = 'error';
    const FTA_DATA = 'fta_data';

    // Dynamic error messages for refund creation blocking
    // type 0 for neither instant nor gateway refund supported
    // type 1 for only instant refund supported
    public static function getBlockRefundsMessage($type = 0, $days = 180)
    {
        $months = intdiv($days, 30);

        switch ($type)
        {
            case 0 :
                return 'Refund is not supported by the bank because the payment is more than ' . $months . ' months old';

            case 1 :
                return 'Payment is more than ' . $months . ' months old, only instant refund is supported';
        }

        return '';
    }
}
