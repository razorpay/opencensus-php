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
}
