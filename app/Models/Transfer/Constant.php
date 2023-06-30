<?php

namespace RZP\Models\Transfer;

use RZP\Models\Feature\Constants as Feature;

final class Constant
{
    // Source types
    const PAYMENT   = 'payment';
    const ORDER     = 'order';
    const MERCHANT  = 'merchant';

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
}
