<?php

namespace RZP\Models\Admin;

use App;
use Cache;

use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Pagination\Entity as PaginationEntity;

class ConfigKey
{
    protected static $fetchedKeys = [];

    const WILDCARD_PERMISSION = '*';

    const PREFIX                                = 'config:';

    // Logs
    const TERMINAL_SELECTION_LOG_VERBOSE        = self::PREFIX . 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = self::PREFIX . 'pricing_rule_selection_log_verbose';
    const THROTTLE_MOCK_LOG_VERBOSE             = self::PREFIX . 'throttle_mock_log_verbose';
    const CURL_INFO_LOG_VERBOSE                 = self::PREFIX . 'curl_info_log_verbose';

    const GATEWAY_PROCESSED_REFUNDS             = self::PREFIX . 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = self::PREFIX . 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = self::PREFIX . 'block_bank_transfers_for_crypto';
    const MERCHANT_ENACH_CONFIGS                = self::PREFIX . 'merchant_enach_configs';
    const SKIP_SLAVE                            = self::PREFIX . 'skip_slave';

    const MASTER_PERCENT                        = self::PREFIX . 'master_percent';

    const DISABLE_MAGIC                         = self::PREFIX . 'disable_magic';
    const NPCI_UPI_DEMO                         = self::PREFIX . 'npci_upi_demo';
    const BLOCK_SMART_COLLECT                   = self::PREFIX . 'block_smart_collect';
    const BLOCK_YESBANK                         = self::PREFIX . 'block_yesbank';
    const BLOCK_AADHAAR_REG                     = self::PREFIX . 'block_aadhaar_reg';
    const HITACHI_DYNAMIC_DESCR_ENABLED         = self::PREFIX . 'hitachi_dynamic_descr_enabled';
    const HITACHI_NEW_URL_ENABLED               = self::PREFIX . 'hitachi_new_url_enabled';
    const FTA_CHANNELS                          = self::PREFIX . 'fta_channels';
    const FTS_PAYOUT_VPA                        = self::PREFIX . 'fts_payout_vpa';
    const FTS_PAYOUT_CARD                       = self::PREFIX . 'fts_payout_card';
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
    const DOWNTIME_DETECTION_CONFIGURATION_V2   = self::PREFIX . '{downtime}:detection:configuration_v2';
    const BENEFICIARY_REGISTRATION              = self::PREFIX . 'beneficiary_registration:';
    const BENEFICIARY_VERIFICATION              = self::PREFIX . 'beneficiary_verification:';
    const FTS_BENEFICIARY                       = self::PREFIX . 'fts_beneficiary';
    const ENABLE_NB_KOTAK_ENCRYPTED_FLOW        = self::PREFIX . 'enable_nb_kotak_encrypted_flow';
    const ENABLE_PAYMENT_DOWNTIME_CARD          = self::PREFIX . 'enable_payment_downtimes_card';
    const ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER   = self::PREFIX . 'enable_payment_downtimes_card_issuer';
    const ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK  = self::PREFIX . 'enable_payment_downtimes_card_network';
    const ENABLE_PAYMENT_DOWNTIME_NETBANKING    = self::PREFIX . 'enable_payment_downtimes_netbanking';
    const ENABLE_PAYMENT_DOWNTIME_UPI           = self::PREFIX . 'enable_payment_downtimes_upi';
    const ENABLE_PAYMENT_DOWNTIME_WALLET        = self::PREFIX . 'enable_payment_downtimes_wallet';

    const ENABLE_DOWNTIME_SERVICE               = self::PREFIX . 'enable_downtime_service';
    const ENABLE_DOWNTIME_SERVICE_CARD          = self::PREFIX . 'enable_downtime_service_card';
    const ENABLE_DOWNTIME_SERVICE_UPI           = self::PREFIX . 'enable_downtime_service_upi';
    const ENABLE_DOWNTIME_SERVICE_NETBANKING    = self::PREFIX . 'enable_downtime_service_netbanking';

    const CARD_PAYMENT_SERVICE_ENABLED          = self::PREFIX . 'card_payment_service_enabled';
    const CARD_PAYMENT_SERVICE_EMI_FETCH        = self::PREFIX . 'card_payment_service_emi_fetch';

    const NB_PLUS_SERVICE_ENABLED               = self::PREFIX . 'nb_plus_service_enabled';

    // Atos and Worldline are same, key on redis is atos
    const WORLDLINE_TID_RANGE_LIST              = self::PREFIX . 'atos_tid_range_list';

    // Gateway level configs
    const PAYSECURE_BLACKLISTED_MCCS            = self::PREFIX . 'paysecure_blacklisted_mccs';

    const LOW_BALANCE_RX_EMAIL                  = self::PREFIX . 'low_balance_rx_email';


    const OFFER_LOG_VERBOSE                     = self::PREFIX . 'offer_log_verbose';

    //Banking account current accounts statement fetch for merchants limit on number of merchants for which to update in one run.
    const BANKING_ACCOUNT_STATEMENT_RATE_LIMIT  = self::PREFIX . 'banking_account_statement_rate_limit';

    CONST RX_BAS_FORCED_FETCH_TIME_IN_HOURS               = self::PREFIX . 'rx_bas_forced_fetch_time_in_hours';

    const GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING = self::PREFIX . 'gateway_balance_last_fetched_at_rate_limiting';

    //Banking account current accounts balance update for merchants .limit on number of merchants for which to update in one run
    const BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = self::PREFIX . 'banking_account_gateway_balance_update_rate_limit';

    // RBL_STATEMENT_FETCH_ATTEMPT_LIMIT is defining the number of attempt count for account statement fetch
    // per request. RBL has internal pagination with flag for statement fetch and we need to refetch with
    // last transaction mentioned to fetch more data.
    const RBL_STATEMENT_FETCH_ATTEMPT_LIMIT         = self::PREFIX . 'rbl_statement_fetch_attempt_limit';

    // special attempt limit for merchants transacting more.
    const RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT = self::PREFIX . 'rbl_statement_fetch_special_attempt_limit';

    const RBL_STATEMENT_FETCH_RETRY_LIMIT           = self::PREFIX . 'rbl_statement_fetch_retry_limit';

    const BLOCK_YESBANK_WALLET_PAYOUTS          = self::PREFIX . 'block_yesbank_wallet_payouts';

    const BLOCK_X_REGISTRATION                  = self::PREFIX. 'block_x_registration';

    const BLOCK_YESBANK_RX_FAV                  = self::PREFIX . 'block_yesbank_rx_fav';

    const REMOVE_SETTLEMENT_BA_COOL_OFF         = self::PREFIX . 'remove_settlement_ba_cool_off';

    const RX_ACCOUNT_NUMBER_SERIES_PREFIX       = self::PREFIX . 'rx_account_number_series_prefix';

    const RX_SHARED_ACCOUNT_ALLOWED_CHANNELS    = self::PREFIX . 'rx_shared_account_allowed_channels';

    // This regex is used to scrub credit card numbers from logs.
    // Currently only banking specific routes will be affected by this
    const CREDIT_CARD_REGEX_FOR_REDACTING       = self::PREFIX . 'credit_card_regex_for_redacting';

    //Following regex is used to scrub credit card numbers from logs only for banking specific routes.
    const EMAIL_REGEX_FOR_REDACTING             = self::PREFIX . 'email_regex_for_redacting';
    const PHONE_NUMBER_REGEX_FOR_REDACTING      = self::PREFIX . 'phone_number_regex_for_redacting';
    const CVV_REGEX_FOR_REDACTING               = self::PREFIX . 'cvv_regex_for_redacting';

    const RX_QUEUED_PAYOUTS_PAGINATION          = self::PREFIX . 'rx_queued_payouts_pagination';
    const RX_QUEUED_PAYOUTS_CRON_LAST_RUN_AT    = self::PREFIX . 'rx_queued_payouts_cron_last_run_at';

    // this is used to limit the number of records fetched while querying db to get low balance configs in order
    // to reduce the load
    const LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH = self::PREFIX . 'low_balance_configs_fetch_limit_in_one_batch';

    const MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT = self::PREFIX . 'merchant_notification_config_fetch_limit';

    // TODO : Remove after June 15 2020 once we can support 25k bulk payouts
    const RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS  = self::PREFIX . 'rx_payouts_custom_batch_file_limit_merchants';
    const RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT       = self::PREFIX . 'rx_payouts_default_max_batch_file_count';

    const BATCH_PAYOUTS_FETCH_LIMIT                     = self::PREFIX . 'batch_payouts_fetch_limit';

    // Count of the number of free shared account payouts allowed per merchant in a month.
    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT = self::PREFIX . FreePayout::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

    // Count of the number of free direct account payouts allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::RBL;

    const FREE_PAYOUTS_SUPPORTED_MODES = self::PREFIX . FreePayout::FREE_PAYOUTS_SUPPORTED_MODES;

    const DELAY_RUPAY_CAPTURE    = self::PREFIX . 'delay_rupay_capture';

    const PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE = self::PREFIX . PaginationEntity::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE;

    // This will be the cutoff based on which we shall decide which flow to show for bulk payouts.
    const BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP = self::PREFIX . 'bulk_payouts_new_merchant_cutoff_timestamp';

    const RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_ACCOUNTS = self::PREFIX . 'rx_va_to_va_payouts_whitelisted_destination_accounts';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::THROTTLE_MOCK_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::SKIP_SLAVE,
        self::MASTER_PERCENT,
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
        self::WORLDLINE_TID_RANGE_LIST,
        self::FTS_PAYOUT_VPA,
        self::FTS_PAYOUT_CARD,
        self::FTS_PAYOUT_BANK_ACCOUNT,
        self::CARD_PAYMENT_SERVICE_ENABLED,
        self::NB_PLUS_SERVICE_ENABLED,
        self::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT,
        self::LOW_BALANCE_RX_EMAIL,
        self::GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING,
        self::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT,
        self::RBL_STATEMENT_FETCH_ATTEMPT_LIMIT,
        self::RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT,
        self::RBL_STATEMENT_FETCH_RETRY_LIMIT,
        self::BLOCK_X_REGISTRATION,
        self::BLOCK_YESBANK_RX_FAV,
        self::REMOVE_SETTLEMENT_BA_COOL_OFF,
        self::BLOCK_YESBANK_WALLET_PAYOUTS,
        self::RX_ACCOUNT_NUMBER_SERIES_PREFIX,
        self::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS,
        self::CREDIT_CARD_REGEX_FOR_REDACTING,
        self::EMAIL_REGEX_FOR_REDACTING,
        self::PHONE_NUMBER_REGEX_FOR_REDACTING,
        self::CVV_REGEX_FOR_REDACTING,
        self::RX_QUEUED_PAYOUTS_PAGINATION,
        self::RX_QUEUED_PAYOUTS_CRON_LAST_RUN_AT,
        self::RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS,
        self::RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT,
        self::CARD_PAYMENT_SERVICE_EMI_FETCH,
        self::LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH,
        self::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT,
        self::BATCH_PAYOUTS_FETCH_LIMIT,
        self::ENABLE_NB_KOTAK_ENCRYPTED_FLOW,
        self::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL,
        self::FREE_PAYOUTS_SUPPORTED_MODES,
        self::ENABLE_PAYMENT_DOWNTIME_CARD,
        self::ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER,
        self::ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK,
        self::ENABLE_PAYMENT_DOWNTIME_NETBANKING,
        self::ENABLE_PAYMENT_DOWNTIME_UPI,
        self::ENABLE_PAYMENT_DOWNTIME_WALLET,
        self::ENABLE_DOWNTIME_SERVICE,
        self::ENABLE_DOWNTIME_SERVICE_CARD,
        self::ENABLE_DOWNTIME_SERVICE_NETBANKING,
        self::ENABLE_DOWNTIME_SERVICE_UPI,
        self::RX_BAS_FORCED_FETCH_TIME_IN_HOURS,
        self::DELAY_RUPAY_CAPTURE,
        self::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE,
        self::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP,
        self::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_ACCOUNTS,
    ];

    const REDIS_CONFIG_MAP = [
        self::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [Name::SET_RX_ACCOUNT_PREFIX],
        self::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS => [Name::SET_SHARED_ACCOUNT_ALLOWED_CHANNELS],
    ];

    /**
     * @param string $key
     * @return array
     */
    public static function fetchPermissionsForKey(string $key) : array
    {
        return self::REDIS_CONFIG_MAP[$key] ?? [];
    }

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
