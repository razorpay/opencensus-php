<?php

namespace RZP\Models\PartnerBankHealth;

class Constants
{
    const MODE               = 'mode';
    const CHANNEL            = 'channel';
    const END_TIME           = 'end_time';
    const START_TIME         = 'start_time';
    const PAYOUT_MODE        = 'payout_mode';
    const EVENT_TYPE_PATTERN = 'event_type_pattern';

    // webhook constants
    const END                = 'end';
    const BANK               = 'bank';
    const BEGIN              = 'begin';
    const STATUS             = 'status';
    const SOURCE             = 'source';
    const METHOD             = 'method';
    const INSTRUMENT         = 'instrument';
    const INTEGRATION_TYPE   = 'integration_type';
    const INCLUDE_MERCHANTS  = 'include_merchants';
    const EXCLUDE_MERCHANTS  = 'exclude_merchants';

    const AFFECTED_MERCHANTS = 'affected_merchants';
}
