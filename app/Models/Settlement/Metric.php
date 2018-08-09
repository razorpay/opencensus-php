<?php

namespace RZP\Models\Settlement;

final class Metric
{
    // ------------------------- Metrics -------------------------

    // ------ Counters ------

    /**
     * Method: Count
     * Dimensions: Channel
     */
    const SETTLEMENTS_CREATED_TOTAL                         = 'settlements_created_total';

    /**
     * Method: Count
     * Dimensions: Channel
     */
    const TRANSACTIONS_PICKED_FOR_SETTLEMENT_TOTAL          = 'transaction_picked_for_settlement_total';

    /**
     * Method: Count
     * Dimensions: SkipReason
     */
    const TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL         = 'transactions_skipped_for_settlement_total';

    /**
     * Method: Count
     * Dimensions: SkipReason
     */
    const MERCHANTS_SKIPPED_FOR_SETTLEMENT_TOTAL            = 'merchants_skipped_for_settlement_total';

    // ------ Histograms ------

    /**
     * Method: Histogram
     * Dimensions: Channel
     */
    const TRANSACTION_SETTLEMENT_INITIATION_DELAY_MINUTES   = 'transaction_settlement_initiation_delay_minutes.histogram';

    // ------------------------- Dimensions -------------------------

    const CHANNEL           = 'channel';

    // ------ Dimension values for failures ------

    // Settlement skip reasons - for transaction & merchant

    const SKIP_REASON                           = 'skip_reason';
    const AUTH_PAYMENT                          = 'auth_payment';
    const REFUND_AUTH_PAYMENT                   = 'refund_auth_payment';
    const BLOCK_WEALTHY_ON_SATURDAY             = 'block_wealthy_on_saturday';
    const BANK_ACCOUNT_CREATED_YESTERDAY        = 'bank_account_created_yesterday';
    const BLOCK_MF_OUTSIDE_TIME_PERIOD          = 'block_mf_outside_time_period';
    const MIN_SETTLEMENT_AMOUNT_BLOCK           = 'min_settlement_amount_block';
    const SETTLEMENT_AMOUNT_LESS_THAN_BALANCE   = 'settlement_amount_less_than_balance';
}
