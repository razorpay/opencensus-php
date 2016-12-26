<?php

namespace RZP\Models\Feature;

class Constants
{
    const ENTITY_IDS      = 'entity_ids';
    const NAMES           = 'names';

    const DUMMY           = 'dummy';
    const WEBHOOKS        = 'webhooks';
    const AGGREGATOR      = 'aggregator';
    const TOKENS          = 'tokens';
    const S2SWALLET       = 's2swallet';
    const S2SUPI          = 's2supi';
    const SETL_REPORT     = 'setl_report';
    const CARD_SAVING     = 'cardsaving';
    const NOFLASHCHECKOUT = 'noflashcheckout';
    const RECURRING       = 'recurring';
    const S2S             = 's2s';
    const INVOICE         = 'invoice';
    const NOZEROPRICING   = 'nozeropricing';
    const REVERSE         = 'reverse';
    const BROKING_REPORT  = 'broking_report';

    public static $allFeatures = [
        self::DUMMY,
        self::WEBHOOKS,
        self::AGGREGATOR,
        self::TOKENS,
        self::S2SWALLET,
        self::S2SUPI,
        self::SETL_REPORT,
        self::CARD_SAVING,
        self::NOFLASHCHECKOUT,
        self::RECURRING,
        self::S2S,
        self::INVOICE,
        self::NOZEROPRICING,
        self::REVERSE,
        self::BROKING_REPORT,
    ];

    // TODO: Use this instead of al;Features once in final code change pr
    public static $featureValueMap = [
        self::DUMMY           => true,
        self::WEBHOOKS        => true,
        self::AGGREGATOR      => true,
        self::TOKENS          => true,
        self::S2SWALLET       => true,
        self::S2SUPI          => true,
        self::SETL_REPORT     => true,
        self::CARD_SAVING     => true,
        self::NOFLASHCHECKOUT => true,
        self::RECURRING       => true,
        self::S2S             => true,
        self::INVOICE         => true,
        self::NOZEROPRICING   => false,
        self::REVERSE         => true,
        self::BROKING_REPORT  => true,
    ];

    public static $visibleFeaturesMap = [
        'flashcheckout' => [
            'feature'      => self::CARD_SAVING,
            'display_name' => 'Flash Checkout'
        ],
        'noflashcheckout' => [
            'feature'      => self::NOFLASHCHECKOUT,
            'display_name' => 'No Flash Checkout'
        ],
        'brokingreport' => [
            'feature'       => self::BROKING_REPORT,
            'display_name'  => 'Broking Report'
        ]
    ];

    public static function getFeatureValue($featureName)
    {
        return self::$featureValueMap[$featureName];
    }
}
