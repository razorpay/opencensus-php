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
     *             Total Merchant count
     */
    const SETTLEMENTS_INITIATE_EXECUTION_TIME               = 'settlements_initiate_execution_time';

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

    /**
     * Method: Count
     */
    const NUMBER_OF_MERCHANTS_IN_QUEUE_FOR_SETTLEMENT       = 'number_of_merchants_in_queue_for_settlement';

    /**
     * Method: Count
     */
    const SETTLEMENT_CREATED_COUNT                          = 'settlement_created_count';

    /**
     * Method: Count
     */
    const MERCHANT_SETTLEMENT_PROCESSED                     = 'merchant_settlement_processed';

    /**
     * Method: Count
     */
    const DISPATCH_FOR_SETTLEMENT_INITIATE                  = 'dispatch_for_settlement_initiate';

    // ------ Histograms ------

    /**
     * Method: Histogram
     * Dimensions: Channel
     */
    const TRANSACTION_SETTLEMENT_INITIATION_DELAY_MINUTES   = 'transaction_settlement_initiation_delay_minutes.histogram';

    // ------------------------- Dimensions -------------------------

    const CHANNEL           = 'channel';
    const MODE              = 'mode';
    const BALANCE_TYPE      = 'balance_type';

    // ------ Dimension values for failures ------

    // Settlement skip reasons - for transaction & merchant

    const SKIP_REASON                           = 'skip_reason';
    const AUTH_PAYMENT                          = 'auth_payment';
    const REFUND_AUTH_PAYMENT                   = 'refund_auth_payment';
    const BLOCK_WEALTHY_ON_SATURDAY             = 'block_wealthy_on_saturday';
    const BANK_ACCOUNT_CREATED_YESTERDAY        = 'bank_account_created_yesterday';
    const BLOCK_MF_OUTSIDE_TIME_PERIOD          = 'block_mf_outside_time_period';
    const MIN_SETTLEMENT_AMOUNT_BLOCK           = 'min_settlement_amount_block';
    const MAX_SETTLEMENT_AMOUNT_BLOCK           = 'max_settlement_amount_block';
    const SETTLEMENT_AMOUNT_LESS_THAN_BALANCE   = 'settlement_amount_less_than_balance';
    const BLOCK_OUTSIDE_ES_WINDOW               = 'block_outside_es_window';
    const BLOCK_OUTSIDE_ES_THREE_PM_WINDOW      = 'block_outside_es_three_pm_window';
    const BLOCK_KARVY_OUTSIDE_TIME_PERIOD       = 'block_karvy_outside_time_period';
    const BENEFICIARY_REGISTRATION_STATUS       = 'bene_reg_status';
    const RECORD_EXIST_PENDING_APPROVAL         = 'record_exist_pending_approval';
    const TOTAL_MERCHANTS_COUNT                 = 'total_merchants_count';
    const USING_QUEUE                           = 'using_queue';
    const TIME_TAKEN_IN_MILLI                   = 'time_taken_in_milli';
}
