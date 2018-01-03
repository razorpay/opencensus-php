<?php

namespace RZP\Models\Pricing;

class DefaultPlan
{
    const FULL_PLAN_ID        = '1AXludj60w4pSp';
    const STARTUP_PLAN_ID     = '2atGxLIYLyHWg7';
    const PROMOTIONAL_PLAN_ID = '1In3Yh5Mluj605';

    public static function getPricingSeedData()
    {
        $pricing1 = self::getStartupPlanSeedData();
        $pricing2 = self::getPromotionalPlanSeedData();
        $pricing3 = self::getZeroPlanSeedData();

        $data = array_merge($pricing1, $pricing2, $pricing3);

        return $data;
    }

    public static function getPromotionalPlanSeedData()
    {
        return [
            [
                'id'             => '1GuENK6Hl2BWGx',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => '290',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1GuENK6Xk6a8I0',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => '290',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1HvFLd6436r9L1',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => '290',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1HvFLd6436r9L2',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'upi',
                'percent_rate'   => '290',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1HvFLd643Fr932',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'aeps',
                'percent_rate'   => '290',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1HvFLd6436r9L3',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '7TGltRgCAKMM51',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'bank_transfer',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1L8dUj9MzP3Bj3',
                'plan_id'        => '1In3Yh5Mluj605',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Nsi8IbQ3pWP7T',
                'plan_id'        => '1In3Yh5Mluj605',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Otj9JcY5qYB9Z',
                'plan_id'        => '1In3Yh5Mluj605',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
        ];
    }

    public static function getStartupPlanSeedData()
    {
        return [
            [
                'id'             => '1a293ji982uFds',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => '250',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1a92ef8iWFE88e',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => '250',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1b03fh9jXGH34f',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => '250',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1b03wh9jXAH42g',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
        ];
    }

    public static function getZeroPlanSeedData()
    {
        return [
            [
                'id'             => '1ZeroPricingR1',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR2',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR3',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR4',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR5',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'bank_transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR6',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR7',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
        ];
    }
}
