<?php

namespace RZP\Models\Customer\Token;

class Constants
{
    const BATCH_IDEMPOTENCY_KEY       = 'idempotency_key';
    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';

    //global customer local cards tokenisation
    const GLOBAL_CUSTOMER_LOCAL_ASYNC_TOKENISATION_QUERY_LIMIT = 20000;
    const LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_TTL = 30 * 24 * 60 * 60;
    const LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_KEY = 'global_customer_local_cards_tokenisation_last_dispatched_token_id';
    const DATA_LAKE_TOKEN_HQ_AGGREGATE_DATA  = "select id , merchant_id ,charge_type , request_count , fee_model ,created_date  from hive.aggregate_pa.token_hq_aggregated_data  where cast(created_date as varchar) = '%s'";
    const DATA_LAKE_TOKEN_HQ_ONBOARD_MERCHANT = "SELECT DISTINCT hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.merchant_id, realtime_terminalslive.terminals.org_id FROM hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1 JOIN realtime_terminalslive.terminals ON hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.merchant_id = realtime_terminalslive.terminals.merchant_id WHERE hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.count_successful >= 1 AND realtime_terminalslive.terminals.type = 'payment' AND realtime_terminalslive.terminals.gateway!='tokenisation_visa' AND realtime_terminalslive.terminals.gateway!='tokenisation_mastercard' AND realtime_terminalslive.terminals.gateway!='tokenisation_rupay' AND realtime_terminalslive.terminals.deleted_at IS NULL AND hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.merchant_id NOT IN (SELECT DISTINCT hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.merchant_id FROM hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1 JOIN realtime_terminalslive.terminals on hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.merchant_id = realtime_terminalslive.terminals.merchant_id WHERE hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1.count_successful >= 1 and (realtime_terminalslive.terminals.gateway='tokenisation_visa' or realtime_terminalslive.terminals.gateway='tokenisation_mastercard' or realtime_terminalslive.terminals.gateway='tokenisation_rupay') and realtime_terminalslive.terminals.status!='activated' and realtime_terminalslive.terminals.deleted_at is NULL)";
    const DATA_LAKE_TOKEN_HQ_PENDING_MERCHANT = "SELECT merchant_id, org_id, gateway, category, terminal_type, terminal_gateway, gateway_terminal_id, provider_name, provider_type from hive.aggregate_pa.tokenisation_terminal_onboarding_mids";
    //token Status
    const FAILED        = 'failed';
    const INITIATED     = 'initiated';
    const ACTIVE        = 'active';
    const DEACTIVATED   = 'deactivated';

    const MERCHANT = 'merchant';
    const ISSUER = 'issuer';
    const TOKEN_HQ_CHARGE = 'token_hq_charge';

    const EMANDATE_CONFIGS          = 'emandate_configs';
    const RETRY_ATTEMPTS            = 'retry_attempts';
    const COOLDOWN_PERIOD           = 'cooldown_period';
    const EMANDATE_TOKEN_STATUS     = 'emandate_token_status';
    const BLOCKED_TEMPORARILY       = 'blocked_temporarily';
    const BLOCKED_PERMANENTLY       = 'blocked_permanently';
    const GATEWAY_ERROR             = "gateway_error";
    const LAST_UPDATED_MONTH        = "last_updated_month";
    const LAST_UPDATED_ON           = "last_updated_on";
    const TOKEN_FLOW                = "token_flow";
    const ONE_TIME_FREQUENCY        = "one_time";
}
