<?php

namespace RZP\Models\Pricing;

use RZP\Exception;

class Feature
{
    const PAYMENT                 = 'payment';
    const PAYOUT                  = 'payout';
    const RECURRING               = 'recurring';
    const TRANSFER                = 'transfer';
    const EMI                     = 'emi';
    const ESAUTOMATIC             = 'esautomatic';
    const ESAUTOMATIC_RESTRICTED  = 'esautomatic_restricted';
    const FUND_ACCOUNT_VALIDATION = 'fund_account_validation';
    const REFUND                  = 'refund';
    const SETTLEMENT_ONDEMAND     = 'settlement_ondemand';
    const OPTIMIZER               = 'optimizer';

    const FEATURE_LIST = [
        self::PAYMENT,
        self::PAYOUT,
        self::RECURRING,
        self::TRANSFER,
        self::EMI,
        self::ESAUTOMATIC,
        self::FUND_ACCOUNT_VALIDATION,
        self::SETTLEMENT_ONDEMAND,
        self::OPTIMIZER,
        self::ESAUTOMATIC_RESTRICTED
    ];

    /**
     * List of features for which pricing rules are optional.
     * If rules are not defined for these features, 0 pricing
     * is applied.
     *
     * IMPORTANT: If you're adding a feature here, ensure to get a
     * rule added for the every method of feature on
     * the zero-pricing plan
     *
     * @var array
     */
    const OPTIONAL_PRICING = [
        self::TRANSFER,
        self::OPTIMIZER
    ];

    public static function validateFeature($feature)
    {
        if (defined(__CLASS__ . '::' . strtoupper($feature)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Pricing feature: ' . $feature);
        }
    }

    /**
     * Whether having pricing rules are optional for the feature
     *
     * @param string $feature
     *
     * @return bool
     */
    public static function isFeaturePricingOptional(string $feature): bool
    {
        return  (in_array($feature, self::OPTIONAL_PRICING, true) === true);
    }
}
