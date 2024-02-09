<?php

namespace RZP\Models\Key;

final class Metric
{
    const KEY_GENERATION_BY_CA_ACTIVATED_MERCHANT_COUNT = 'key_generation_by_ca_activated_merchant_count';

    const PRIVATE_X_ROUTE_HITS_BY_CA_ACTIVATED_MERCHANT_COUNT = 'private_x_route_hits_by_ca_activated_merchant_count';

    const PUBLIC_X_PAYOUT_LINKS_ROUTE_HITS_BY_CA_ACTIVATED_MERCHANT_COUNT = 'public_x_payout_links_route_hits_by_ca_activated_merchant_count';

    const ENTITY_ORIGIN_CREATE_FAILED_TOTAL = 'create_failed_entity_origin_total';

    const ENTITY_ORIGIN_CREATE_FROM_PAYMENT_PUBLIC_KEY = 'create_entity_origin_from_payment_public_key_total';

    const ENTITY_ORIGIN_CREATE_FROM_ORDER_PUBLIC_KEY = 'create_entity_origin_from_order_public_key_total';

    const ENTITY_ORIGIN_OWNER_CACHE_HIT_TOTAL  = 'entity_origin_owner_cache_hit_total';
    const ENTITY_ORIGIN_FETCH_FROM_CACHE_FAILURE_TOTAL  = 'entity_origin_fetch_from_cache_failure_total';

    const ENTITY_ORIGIN_OWNER_CACHE_MISS_TOTAL = 'entity_origin_owner_cache_miss_total';

    const ENTITY_ORIGIN_OWNER_MASTER_QUERY_TOTAL = 'entity_origin_owner_master_query_total';
}
