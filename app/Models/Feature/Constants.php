<?php

namespace RZP\Models\Feature;

class Constants
{
    const ENTITY_IDS               = 'entity_ids';
    const NAMES                    = 'names';
    const DUMMY                    = 'dummy';
    const WEBHOOKS                 = 'webhooks';
    const AGGREGATOR               = 'aggregator';
    const TOKENS                   = 'tokens';
    const S2SWALLET                = 's2swallet';
    const S2SUPI                   = 's2supi';
    const S2SAEPS                  = 's2saeps';
    const SETL_REPORT              = 'setl_report';
    const NOFLASHCHECKOUT          = 'noflashcheckout';
    const RECURRING                = 'recurring';
    const S2S                      = 's2s';
    const INVOICE                  = 'invoice';
    const NOZEROPRICING            = 'nozeropricing';
    const REVERSE                  = 'reverse';
    const BROKING_REPORT           = 'broking_report';
    const DSP_REPORT               = 'dsp_report';
    const RPP_REPORT               = 'rpp_report';
    const AGGREGATOR_REPORT        = 'aggregator_report';
    const PAYMENT_EMAIL_FETCH      = 'payment_email_fetch';
    const CREATED_FLOW             = 'created_flow';
    const PAYOUT                   = 'payout';
    const OPENWALLET               = 'openwallet';
    const MARKETPLACE              = 'marketplace';
    const EMAIL_OPTIONAL           = 'email_optional';
    const CONTACT_OPTIONAL         = 'contact_optional';
    const SUBSCRIPTIONS            = 'subscriptions';
    const ZOHO                     = 'zoho';
    const EXPOSE_DOWNTIMES         = 'expose_downtimes';
    const PAYMENT_FAILURE_EMAIL    = 'payment_failure_email';
    const VIRTUAL_ACCOUNTS         = 'virtual_accounts';
    const INVOICE_PARTIAL_PAYMENTS = 'invoice_partial_payments';
    const HIDE_DOWNTIMES           = 'hide_downtimes';
    const OLD_CREDITS_FLOW         = 'old_credits_flow';
    const CHARGE_AT_WILL           = 'charge_at_will';
    const EMI_MERCHANT_SUBVENTION  = 'emi_merchant_subvention';
    const FSS_RISK_UDF             = 'fss_risk_udf';
    const RULE_FILTER              = 'rule_filter';
    const TPV                      = 'tpv';
    const IRCTC_REPORT             = 'irctc_report';

    // TODO: Use this instead of allFeatures once in final code change pr
    public static $featureValueMap = [
        self::DUMMY                    => true,
        self::WEBHOOKS                 => true,
        self::AGGREGATOR               => true,
        self::TOKENS                   => true,
        self::S2SWALLET                => true,
        self::S2SUPI                   => true,
        self::S2SAEPS                  => true,
        self::SETL_REPORT              => true,
        self::NOFLASHCHECKOUT          => true,
        self::RECURRING                => true,
        self::S2S                      => true,
        self::INVOICE                  => true,
        self::NOZEROPRICING            => false,
        self::REVERSE                  => true,
        self::BROKING_REPORT           => true,
        self::DSP_REPORT               => true,
        self::RPP_REPORT               => true,
        self::AGGREGATOR_REPORT        => true,
        self::PAYMENT_EMAIL_FETCH      => true,
        self::CREATED_FLOW             => true,
        self::PAYOUT                   => true,
        self::OPENWALLET               => true,
        self::MARKETPLACE              => true,
        self::EMAIL_OPTIONAL           => true,
        self::CONTACT_OPTIONAL         => true,
        self::SUBSCRIPTIONS            => true,
        self::ZOHO                     => true,
        self::EXPOSE_DOWNTIMES         => true,
        self::PAYMENT_FAILURE_EMAIL    => true,
        self::VIRTUAL_ACCOUNTS         => true,
        self::INVOICE_PARTIAL_PAYMENTS => true,
        self::HIDE_DOWNTIMES           => true,
        self::OLD_CREDITS_FLOW         => true,
        self::CHARGE_AT_WILL           => true,
        self::EMI_MERCHANT_SUBVENTION  => true,
        self::FSS_RISK_UDF             => true,
        self::RULE_FILTER              => true,
        self::TPV                      => true,
        self::IRCTC_REPORT             => true,
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
