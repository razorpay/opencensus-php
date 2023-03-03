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

    //token Status
    const FAILED        = 'failed';
    const INITIATED     = 'initiated';
    const ACTIVE        = 'active';
    const DEACTIVATED   = 'deactivated';

    const MERCHANT = 'merchant';
    const ISSUER = 'issuer';
    const TOKEN_HQ_CHARGE = 'token_hq_charge';
}
