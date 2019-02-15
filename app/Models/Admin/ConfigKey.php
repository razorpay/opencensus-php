<?php

namespace RZP\Models\Admin;

use App;
use Cache;

class ConfigKey
{
    const TERMINAL_SELECTION_LOG_VERBOSE        = 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = 'pricing_rule_selection_log_verbose';
    const GATEWAY_PROCESSED_REFUNDS             = 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = 'block_bank_transfers_for_crypto';
    const MERCHANT_ENACH_CONFIGS                = 'merchant_enach_configs';
    const SKIP_SLAVE                            = 'skip_slave';
    const MASTER_PERCENT                        = 'master_percent';
    const HEARTBEAT_ROUTES                      = 'heartbeat_routes';
    const HEARTBEAT_TRAFFIC_PERCENTAGE          = 'heartbeat_traffic_percentage';
    const HEARTBEAT_ENABLED                     = 'heartbeat_enabled';
    const HEARTBEAT_MOCK                        = 'heartbeat_mock';
    const HEARTBEAT_TIME_THRESHOLD              = 'heartbeat_time_threshold';
    const DISABLE_MAGIC                         = 'disable_magic';
    const NPCI_UPI_DEMO                         = 'npci_upi_demo';
    const BLOCK_SMART_COLLECT                   = 'block_smart_collect';
    const BLOCK_YESBANK                         = 'block_yesbank';
    const BLOCK_AADHAAR_REG                     = 'block_aadhaar_reg';
    const HITACHI_DYNAMIC_DESCR_ENABLED         = 'hitachi_dynamic_descr_enabled';
    const FTS_CHANNELS                          = 'fts_channels';
    const CPS_SERVICE_ENABLED                   = 'cps_service_enabled';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::SKIP_SLAVE,
        self::MASTER_PERCENT,
        self::HEARTBEAT_ROUTES,
        self::HEARTBEAT_TRAFFIC_PERCENTAGE,
        self::HEARTBEAT_ENABLED,
        self::HEARTBEAT_MOCK,
        self::HEARTBEAT_TIME_THRESHOLD,
        self::DISABLE_MAGIC,
        self::NPCI_UPI_DEMO,
        self::BLOCK_SMART_COLLECT,
        self::BLOCK_YESBANK,
        self::BLOCK_AADHAAR_REG,
        self::HITACHI_DYNAMIC_DESCR_ENABLED,
        self::CPS_SERVICE_ENABLED,
    ];

    public static function isSensitive(string $key)
    {
        if (in_array($key, self::PUBLIC_KEYS, true) === true)
        {
            return false;
        }

        return true;
    }

    public static function get($key, $default = null)
    {
        $app = App::getFacadeRoot();

        $data = $default;

        try
        {
            $data = Cache::get($key);
        }
        catch (\Throwable $ex)
        {
            $app['trace']->traceException($ex);
        }

        return $data;
    }
}
