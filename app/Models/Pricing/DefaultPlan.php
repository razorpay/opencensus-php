<?php

namespace RZP\Models\Pricing;

use RZP\Models\Admin\Org;

class DefaultPlan
{
    const FULL_PLAN_ID                = '1AXludj60w4pSp';
    const STARTUP_PLAN_ID             = '2atGxLIYLyHWg7';
    const PROMOTIONAL_PLAN_ID         = '1In3Yh5Mluj605';
    const HDFC_PROMOTIONAL_PLAN_ID    = 'BAJq6FJDNJ4ZqD';
    const BOB_PROMOTIONAL_PLAN_ID     = 'BAJvpnuxy4AUq3';
    const DIWALI_PROMOTIONAL_PLAN_ID  = 'BI7O6FmHlzLFZm';


    public static function getPricingSeedData()
    {
        $startupPlan = self::getStartupPlanSeedData();
        $promoPlan   = self::getPromotionalPlanSeedData();
        $zeroPlan    = self::getZeroPlanSeedData();
        $bankingPlan = self::getBankingPlanData();

        return array_merge(
            $startupPlan,
            $promoPlan,
            $zeroPlan,
            $bankingPlan);
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1GuENK6Xk6a8I1',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Full Price',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => '0',
                'fixed_rate'     => '10',
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Otj9JcY5qYB92',
                'plan_id'        => '1AXludj60w4pSp',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Otj9JcY6dYB9Z',
                'plan_id'        => '1In3Yh5Mluj605',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1L8dUj9MzP3Bj4',
                'plan_id'        => 'BAJq6FJDNJ4ZqD',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::HDFC_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Nsi8IbQ3pWP7A',
                'plan_id'        => 'BAJq6FJDNJ4ZqD',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::HDFC_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Otj9JcY5qYB9X',
                'plan_id'        => 'BAJq6FJDNJ4ZqD',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => '200',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::HDFC_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1Otj9JcY6dYB9Y',
                'plan_id'        => 'BAJq6FJDNJ4ZqD',
                'plan_name'      => 'Promotional Price',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::HDFC_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1a92ef8iWFE881',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => '0',
                'fixed_rate'     => '20',
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1b03wh9jXAH421',
                'plan_id'        => '2atGxLIYLyHWg7',
                'plan_name'      => 'Startup Plan',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => '0',
                'fixed_rate'     => '0',
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR8',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
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
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1ZeroPricingR9',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
            [
                'id'             => '1CEmiPricingR1',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'cardless_emi',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],

            [
                'id'             => '1PLatPricingR1',
                'plan_id'        => '10ZeroPricingP',
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'paylater',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'     => null,
                'created_at'     => time(),
                'updated_at'     => time()
            ],
        ];
    }

    public static function getBankingPlanData(): array
    {
        return [
            // Rs 5 for payout value < Rs 1k for payouts with method = fund_transfer.
            [
                'id'                  => 'Bbg7cl6t6I3XA5',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'fixed_rate'          => 500,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            // Rs 9 for payout value between Rs 1k and 25k for payouts with method = fund_transfer.
            [
                'id'                  => 'Bbg7dTcURsOr77',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'fixed_rate'          => 900,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            // Rs 15 for payout value > Rs 25k for payouts with method = fund_transfer.
            [
                'id'                  => 'Bbg7e4oKCgaubd',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => \RZP\Models\Base\ExtendedValidations::MYSQL_UNSIGNED_INT_MAX,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            // Rs 5 for payout value < Rs 1k for payouts with method = upi.
            [
                'id'                  => 'Bbg7eYLkxM7sLP',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 500,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            // Rs 9 for payout value between Rs 1k and 25k for payouts with method = upi.
            [
                'id'                  => 'Bbg7f0FaUJQOvj',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 900,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            // Rs 15 for payout value > Rs 25k for payouts with method = upi.
            [
                'id'                  => 'Bbg7fgaDwax03u',
                'plan_id'             => 'BTo98voDY05ueB',
                'plan_name'           => 'Banking default plan',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => \RZP\Models\Base\ExtendedValidations::MYSQL_UNSIGNED_INT_MAX,
                'org_id'              => Org\Entity::RAZORPAY_ORG_ID,
                'expired_at'          => null,
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
        ];
    }
}
