<?php

namespace RZP\Constants;

final class ChargeCollections
{
    // Charge collection event fields for PS(payouts service) push event
    const CHARGE_COLLECTION_EVENT_PUSH_PS_ID                    = 'id';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_MERCHANT_ID           = 'merchant_id';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_CHANNEL               = 'channel';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_MODE                  = 'mode';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_STATUS                = 'status';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_AMOUNT                = 'amount';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_INTERFACE             = 'interface';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_AGGREGATION           = 'aggregation';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_EVENT_TYPE            = 'event_type';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_PAYLOAD_SOURCE        = 'payload_source';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_FEATURE               = 'feature';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_SOURCE_ACCOUNT_NUMBER = 'source_account_number';
    const CHARGE_COLLECTION_EVENT_PUSH_PS_SOURCE_ACCOUNT_TYPE   = 'source_account_type';
}
