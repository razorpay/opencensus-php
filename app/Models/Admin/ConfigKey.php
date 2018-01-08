<?php

namespace RZP\Models\Admin;

class ConfigKey
{
    const TERMINAL_SELECTION_LOG_VERBOSE        = 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = 'pricing_rule_selection_log_verbose';
    const GATEWAY_PROCESSED_REFUNDS             = 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = 'block_bank_transfers_for_crypto';
    const SKIP_SLAVE                            = 'skip_slave';
    const EXPERIMENTAL_RECURRING_FILTER         = 'experimental_recurring_filter';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::SKIP_SLAVE,
        self::EXPERIMENTAL_RECURRING_FILTER,
    ];

    public static function isSensitive(string $key)
    {
        if (in_array($key, self::PUBLIC_KEYS, true) === true)
        {
            return false;
        }

        return true;
    }
}
