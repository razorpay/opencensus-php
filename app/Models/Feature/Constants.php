<?php

namespace RZP\Models\Feature;

class Constants
{
    const ENTITY_IDS    = 'entity_ids';
    const NAMES         = 'names';

    const DUMMY         = 'dummy';
    const WEBHOOKS      = 'webhooks';
    const AGGREGATOR    = 'aggregator';
    const TOKENS        = 'tokens';
    const S2SWALLET     = 's2swallet';
    const SETL_REPORT   = 'setl_report';
    const CARD_SAVING   = 'cardsaving';
    const RECURRING     = 'recurring';
    const S2S           = 's2s';

    public static $allFeatures = [
        self::DUMMY,
        self::WEBHOOKS,
        self::AGGREGATOR,
        self::TOKENS,
        self::S2SWALLET,
        self::SETL_REPORT,
        self::CARD_SAVING,
        self::RECURRING,
        self::S2S
    ];
}
