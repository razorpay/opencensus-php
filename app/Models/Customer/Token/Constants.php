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
    const GLOBAL_CUSTOMER_LOCAL_ASYNC_TOKENISATION_QUERY_LIMIT = 5000;
    const LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_TTL = 30 * 24 * 60 * 60;
    const LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_KEY = 'global_customer_local_cards_tokenisation_last_dispatched_token_id';
}
