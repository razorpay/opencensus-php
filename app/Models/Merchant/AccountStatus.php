<?php

namespace RZP\Models\Merchant;

/**
 * Holds different values for query parameter ACCOUNT_STATUS.
 */
final class AccountStatus
{
    const ALL       = 'all';
    const SUSPENDED = 'suspended';
    const ARCHIVED  = 'archived';
    const ACTIVATED = 'activated';
    const PENDING   = 'pending';
    const DEAD      = 'dead';
}
