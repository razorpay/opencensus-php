<?php

namespace RZP\Models\Transfer;

use RZP\Models\Feature\Constants as Feature;

final class Constant
{
    // Source types
    const PAYMENT   = 'payment';
    const ORDER     = 'order';
    const MERCHANT  = 'merchant';
    const TRANSFER  = 'transfer';

    // platform type transfer
    const PLATFORM          = 'platform';
    const REGULAR           = 'regular';
    const PARTNER_DETAILS   = 'partner_details';
    const EMAIL             = 'email';
    const FEATURE_ENABLED   = 'feature_enabled';

    const EXCLUDED_LINKED_ACCOUNTS = 'excluded_linked_accounts';
    const INCLUDED_LINKED_ACCOUNTS = 'included_linked_accounts';


    // Attempts
    const MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS = 1;
    const MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS   = 4;

    // Public statuses
    const FETCH_STATUS = [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED];

    // Fetch transfers by chunk for recon
    const CHUNK = 500;

    // Retry transfer processing in case of DbQueryException
    const TRANSFER_PROCESS_RETRIES = 2;

    const BALANCE_UPDATE_WITH_OLD_BALANCE_CHECK_FAILED = 'balance_update_with_old_balance_check_failed';

    public static $keyMerchantFeatureIdentifiers = [
        Feature::ROUTE_KEY_MERCHANTS_QUEUE,
        Feature::CAPITAL_FLOAT_ROUTE_MERCHANT,
        Feature::SLICE_ROUTE_MERCHANT,
    ];

    const CATEGORY_1_MCC = ['6211'];

    const CATEGORY_2_MCC = ['6012', '4900', '9399'];

    const CATEGORY_1 = 'category1';
    const CATEGORY_2 = 'category2';
    const CATEGORY_3 = 'category3';

    const TRANSFER_PROCESS_MUTEX_NUM_RETRIES_KEY = 'num_retries';
    const TRANSFER_PROCESS_MUTEX_MIN_RETRY_DELAY_MS_KEY = 'min_delay_ms';

    const TRANSFER_PROCESS_MUTEX_MAX_RETRY_DELAY_MS_KEY = 'max_delay_ms';

    const TRANSFER_PROCESS_MUTEX_LOCK_TIMEOUT_SEC_KEY = 'lock_timeout_sec';

    const DEDICATED_QUEUE_ONE   = 'dedicated_queue_one';
    const DEDICATED_QUEUE_TWO   = 'dedicated_queue_two';
    const DEDICATED_QUEUE_THREE = 'dedicated_queue_three';
    const DEDICATED_QUEUE_FOUR  = 'dedicated_queue_four';
    const DEDICATED_QUEUE_FIVE  = 'dedicated_queue_five';
    const DEDICATED_QUEUE_MALAYSIA  = 'dedicated_queue_malaysia';

    /**
     * For enabling merchants on async balance debit for transfer debit transactions
     * which are on zero pricing model for transfers. This will create a debit transaction
     * without fee calculation and consider fee and tax as 0.
     */
    const MIDS_FOR_ASYNC_BALANCE_UPDATE_FOR_TRANSFER_DEBIT_TXNS = [
        'EtHJCtiuRSZRCz',
    ];

    /**
     * For enabling merchants on async balance debit for transfer debit transactions
     * which are on non-zeo pricing model for transfers. This will create a debit transaction
     * with fee calculation.
     * NOTE: This skips credit calculation, so this should be enabled with caution only for
     * merchants which have 0 credits.
     */
    const MIDS_FOR_ASYNC_BALANCE_UPDATE_FOR_TRANSFER_DEBIT_TXNS_WITH_FEE = [
    ];
}
