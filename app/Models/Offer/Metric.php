<?php

namespace RZP\Models\Offer;

final class Metric
{
    const OFFERS_ENGINE_REQUEST_FAILURE = 'offers_engine_request_failure';

    const OFFERS_ENGINE_CREATE_OFFER_RESPONSE_NIL          = 'offers_engine_create_offer_response_nil';

    const OFFERS_ENGINE_CREATE_OFFER_FAIL       = 'offers_engine_create_offer_fail';

    const CREATE_OFFER_RESPONSE_MISMATCH             = 'create_offer_response_mismatch';

    const OFFERS_ENGINE_UPDATE_OFFER_RESPONSE_NIL          = 'offers_engine_update_offer_response_nil';

    const OFFERS_ENGINE_UPDATE_OFFER_FAIL          = 'offers_engine_update_offer_fail';

    const OFFERS_ENGINE_FETCH_BY_ID_RESPONSE_NIL          = 'offers_engine_fetch_by_id_response_nil';

    const OFFERS_ENGINE_FETCH_BY_ID_INVALID_RESPONSE          = 'offers_engine_fetch_by_id_invalid_response';

    const OFFERS_ENGINE_FETCH_BY_ID_FAIL          = 'offers_engine_fetch_by_id_fail';
    const OFFERS_ENGINE_AGGREGATE_FETCH_BY_ID_FAIL          = 'offers_engine_aggregate_fetch_by_id_fail';

    const OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFER_BY_ID_FAIL          = 'offers_engine_fetch_subscription_offer_by_id_fail';

    const OFFERS_ENGINE_FETCH_ACTIVE_NONSUBSCRIPTION_OFFERS_FAIL          = 'offers_engine_fetch_active_nonsubscription_offes_fail';

    const OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFERS_FAIL         = 'offers_engine_fetch_subscription_offers_fail';

    const OFFERS_ENGINE_FETCH_OFFERS_FAIL          = 'offers_engine_fetch_offers_fail';
    const OFFERS_ENGINE_API_FALLBACK_COUNTER          = 'offers_engine_api_fallback_counter';

    const OFFERS_ENGINE_FETCH_OFFERS_FAIL_FOR_PAYMENTS          = 'offers_engine_fetch_offers_fail_for_payments';

    const OFFERS_ENGINE_FETCH_DEFAULT_OFFERS_FAIL          = 'offers_engine_fetch_default_offers_fail';

    const OFFERS_ENGINE_VALIDATE_OFFER_FAIL          = 'offers_engine_validate_offer_fail';
    const OFFERS_ENGINE_DISCOUNT_MISMATCH          = 'offers_engine_discount_mismatch';
    const OFFERS_ENGINE_TRANSACTION_FAILURE          = 'offers_engine_transaction_failure';

    const OFFERS_PAYMENT_CREATION_INVALID          = 'offers_payment_creation_invalid';

    const OFFERS_ENGINE_ORDER_APPLICABILITY_DIFF          = 'offers_engine_order_applicability_diff';
}
