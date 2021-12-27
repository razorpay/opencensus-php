<?php

namespace RZP\Models\Transfer;

final class Constant
{
    // Source types
    const PAYMENT   = 'payment';
    const ORDER     = 'order';
    const MERCHANT  = 'merchant';

    // Attempts
    const MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS = 1;
    const MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS   = 4;

    // Public statuses
    const FETCH_STATUS = [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED];

    // Fetch transfers by chunk for recon
    const CHUNK = 500;

    // Retry transfer processing in case of DbQueryException
    const TRANSFER_PROCESS_RETRIES = 2;

    const MUTEX_LOCK_ON_LINKED_ACCOUNT_ID_NOT_ACQUIRED = 'mutex_lock_on_linked_account_id_not_acquired';
}
