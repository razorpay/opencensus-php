<?php

namespace RZP\Models\Admin;

use App;
use Cache;

class ConfigKey
{
    protected static $fetchedKeys = [];

    const PREFIX                                = 'config:';

    // Logs
    const TERMINAL_SELECTION_LOG_VERBOSE        = self::PREFIX . 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = self::PREFIX . 'pricing_rule_selection_log_verbose';
    const HEARTBEAT_LOG_VERBOSE                 = self::PREFIX . 'heartbeat_log_verbose';
    const THROTTLE_MOCK_LOG_VERBOSE             = self::PREFIX . 'throttle_mock_log_verbose';
    const CURL_INFO_LOG_VERBOSE                 = self::PREFIX . 'curl_info_log_verbose';

    const GATEWAY_PROCESSED_REFUNDS             = self::PREFIX . 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = self::PREFIX . 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = self::PREFIX . 'block_bank_transfers_for_crypto';
    const MERCHANT_ENACH_CONFIGS                = self::PREFIX . 'merchant_enach_configs';
    const SKIP_SLAVE                            = self::PREFIX . 'skip_slave';

    const MASTER_PERCENT                        = self::PREFIX . 'master_percent';
    const HEARTBEAT_MOCK                        = self::PREFIX . 'heartbeat_mock';
    const HEARTBEAT_ROUTES                      = self::PREFIX . 'heartbeat_routes';
    const HEARTBEAT_ENABLED                     = self::PREFIX . 'heartbeat_enabled';
    const HEARTBEAT_FORCE_RUN                   = self::PREFIX . 'heartbeat_force_run';
    const HEARTBEAT_TIME_THRESHOLD              = self::PREFIX . 'heartbeat_time_threshold';
    const HEARTBEAT_TRAFFIC_PERCENTAGE          = self::PREFIX . 'heartbeat_traffic_percentage';
    const HEARTBEAT_SLAVE_TIME_THRESHOLD        = self::PREFIX . 'heartbeat_slave_time_threshold';

    const DISABLE_MAGIC                         = self::PREFIX . 'disable_magic';
    const NPCI_UPI_DEMO                         = self::PREFIX . 'npci_upi_demo';
    const BLOCK_SMART_COLLECT                   = self::PREFIX . 'block_smart_collect';
    const BLOCK_YESBANK                         = self::PREFIX . 'block_yesbank';
    const BLOCK_AADHAAR_REG                     = self::PREFIX . 'block_aadhaar_reg';
    const HITACHI_DYNAMIC_DESCR_ENABLED         = self::PREFIX . 'hitachi_dynamic_descr_enabled';
    const HITACHI_NEW_URL_ENABLED               = self::PREFIX . 'hitachi_new_url_enabled';
    const FTA_CHANNELS                          = self::PREFIX . 'fta_channels';
    const FTS_CHANNELS                          = self::PREFIX . 'fts_channels';
    const FTS_PAYOUT_VPA                        = self::PREFIX . 'fts_payout_vpa';
    const FTS_PAYOUT_CARD                       = self::PREFIX . 'fts_payout_card';
    const FTS_TRANSFER_SLA                      = self::PREFIX . 'fts_transfer_sla';
    const FTS_TEST_MERCHANT                     = self::PREFIX . 'fts_test_merchant';
    const FTS_ROUTE_PERCENTAGE                  = self::PREFIX . 'fts_request_percentage';
    const FTS_PAYOUT_BANK_ACCOUNT               = self::PREFIX . 'fts_payout_bank_account';
    const CPS_SERVICE_ENABLED                   = self::PREFIX . 'cps_service_enabled';
    const SETTLEMENT_TRANSACTION_LIMIT          = self::PREFIX . 'settlement_transaction_limit';
    const ENABLE_PAYMENT_DOWNTIMES              = self::PREFIX . 'enable_payment_downtimes';
    const DOWNTIME_THROTTLE                     = self::PREFIX . 'downtime:throttle';
    const DOWNTIME_DETECTION                    = self::PREFIX . '{downtime:detection}';
    const RX_SLA_FOR_IMPS_PAYOUT                = self::PREFIX . 'rx_sla_for_imps_payout';
    const DOWNTIME_DETECTION_CONFIGURATION      = self::PREFIX . 'downtime:detection:configuration';
    const BENEFICIARY_REGISTRATION              = self::PREFIX . 'beneficiary_registration:';
    const BENEFICIARY_VERIFICATION              = self::PREFIX . 'beneficiary_verification:';
    const CITI_CHANNEL_PAYOUT_MIDS              = self::PREFIX . 'citi_channel_payout_mids';
    const ICICI_CHANNEL_PAYOUT_MIDS             = self::PREFIX . 'icici_channel_payout_mids';

    const ATOS_TID_RANGE_LIST                   = self::PREFIX . 'atos_tid_range_list';

    // Gateway level configs
    const PAYSECURE_BLACKLISTED_MCCS            = self::PREFIX . 'paysecure_blacklisted_mccs';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::HEARTBEAT_LOG_VERBOSE,
        self::THROTTLE_MOCK_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::HEARTBEAT_FORCE_RUN,
        self::SKIP_SLAVE,
        self::MASTER_PERCENT,
        self::HEARTBEAT_TRAFFIC_PERCENTAGE,
        self::HEARTBEAT_ENABLED,
        self::HEARTBEAT_MOCK,
        self::HEARTBEAT_TIME_THRESHOLD,
        self::HEARTBEAT_SLAVE_TIME_THRESHOLD,
        self::DISABLE_MAGIC,
        self::NPCI_UPI_DEMO,
        self::BLOCK_SMART_COLLECT,
        self::BLOCK_YESBANK,
        self::BLOCK_AADHAAR_REG,
        self::HITACHI_DYNAMIC_DESCR_ENABLED,
        self::CPS_SERVICE_ENABLED,
        self::SETTLEMENT_TRANSACTION_LIMIT,
        self::ENABLE_PAYMENT_DOWNTIMES,
        self::FTS_TEST_MERCHANT,
        self::CURL_INFO_LOG_VERBOSE,
        self::HITACHI_NEW_URL_ENABLED,
        self::PAYSECURE_BLACKLISTED_MCCS,
        self::DOWNTIME_THROTTLE,
        self::RX_SLA_FOR_IMPS_PAYOUT,
        self::ATOS_TID_RANGE_LIST,
        self::FTS_PAYOUT_VPA,
        self::FTS_PAYOUT_CARD,
        self::FTS_PAYOUT_BANK_ACCOUNT,
        self::CITI_CHANNEL_PAYOUT_MIDS,
        self::ICICI_CHANNEL_PAYOUT_MIDS,
    ];

    public static function isSensitive(string $key)
    {
        if (in_array($key, self::PUBLIC_KEYS, true) === true)
        {
            return false;
        }

        return true;
    }

    public static function resetFetchedKeys()
    {
        static::$fetchedKeys = [];
    }

    public static function get($key, $default = null)
    {
        if (isset(static::$fetchedKeys[$key]) === true)
        {
            return static::$fetchedKeys[$key];
        }

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

        static::$fetchedKeys[$key] = $data;

        return $data;
    }
}
