<?php

namespace RZP\Models\Admin;

class ConfigKey
{
    const TERMINAL_SELECTION_LOG_VERBOSE        = 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = 'pricing_rule_selection_log_verbose';
    const GATEWAY_PROCESSED_REFUNDS             = 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = 'block_bank_transfers_for_crypto';
    const MERCHANT_ENACH_CONFIGS                = 'merchant_enach_configs';
    const SKIP_SLAVE                            = 'skip_slave';
    const DISABLE_MAGIC                         = 'disable_magic';
    const NPCI_UPI_DEMO                         = 'npci_upi_demo';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::SKIP_SLAVE,
        self::DISABLE_MAGIC,
        self::NPCI_UPI_DEMO,
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
