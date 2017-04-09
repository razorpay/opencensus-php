<?php

namespace RZP\Models\Feature;

class Constants
{
    const ENTITY_IDS          = 'entity_ids';
    const NAMES               = 'names';

    const DUMMY               = 'dummy';
    const WEBHOOKS            = 'webhooks';
    const AGGREGATOR          = 'aggregator';
    const TOKENS              = 'tokens';
    const S2SWALLET           = 's2swallet';
    const S2SUPI              = 's2supi';
    const SETL_REPORT         = 'setl_report';
    const NOFLASHCHECKOUT     = 'noflashcheckout';
    const RECURRING           = 'recurring';
    const S2S                 = 's2s';
    const INVOICE             = 'invoice';
    const NOZEROPRICING       = 'nozeropricing';
    const REVERSE             = 'reverse';
    const BROKING_REPORT      = 'broking_report';
    const PAYMENT_EMAIL_FETCH = 'payment_email_fetch';
    const CREATED_FLOW        = 'created_flow';
    const PAYOUT              = 'payout';
    const OPENWALLET          = 'openwallet';
    const MARKETPLACE         = 'marketplace';
    const EMAIL_OPTIONAL      = 'email_optional';
    const CONTACT_OPTIONAL    = 'contact_optional';

    // TODO: Use this instead of allFeatures once in final code change pr
    public static $featureValueMap = [
        self::DUMMY               => true,
        self::WEBHOOKS            => true,
        self::AGGREGATOR          => true,
        self::TOKENS              => true,
        self::S2SWALLET           => true,
        self::S2SUPI              => true,
        self::SETL_REPORT         => true,
        self::NOFLASHCHECKOUT     => true,
        self::RECURRING           => true,
        self::S2S                 => true,
        self::INVOICE             => true,
        self::NOZEROPRICING       => false,
        self::REVERSE             => true,
        self::BROKING_REPORT      => true,
        self::PAYMENT_EMAIL_FETCH => true,
        self::CREATED_FLOW        => true,
        self::PAYOUT              => true,
        self::OPENWALLET          => true,
        self::MARKETPLACE         => true,
        self::EMAIL_OPTIONAL      => true,
        self::CONTACT_OPTIONAL    => true,
    ];

    public static $visibleFeaturesMap = [
        'noflashcheckout' => [
            'feature'      => self::NOFLASHCHECKOUT,
            'display_name' => 'No Flash Checkout'
        ]
    ];

    public static function getFeatureValue($featureName)
    {
        return self::$featureValueMap[$featureName];
    }
}
