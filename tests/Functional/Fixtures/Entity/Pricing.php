<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models;
use RZP\Tests\Functional\Partner\Constants;

class Pricing extends Base
{
    const DEFAULT_PRICING_PLAN_ID = '1hDYlICobzOCYt';

    public function createDefaultPlan()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1nvp2XPMmaRLxb',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1OwH8rTI0ejFxS',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'AMEX',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1fq0OXpgeyafQq',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'DICL',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1nwo5YENadEFvf',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 1,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOyaqpB',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOyaqpC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'HDFC',
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpD',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpfOJaqqE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar_fp',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD01Xpe3JaqpF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar_fp',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyrD',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyaF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3QYFf1zbys6',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'aeps',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zhyaE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 500,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYf21zbybG',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybGCYf2',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybGCab2',
                'plan_id'             => 'A8UwvIbaL8n4Q8',
                'plan_name'           => 'testDefaultQrPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'receiver_type'       => 'qr_code',
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybabab2',
                'plan_id'             => 'ArGUUem5z3UADv',
                'plan_name'           => 'testDefaultEmiPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab2',
                'plan_id'             => 'A8UwvIbaL8n4Q8',
                'plan_name'           => 'testDefaultQrPlan',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'receiver_type'       => 'qr_code',
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab3',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'cardless_emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab4',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'fund_account_validation',
                'payment_method'      => 'bank_account',
                'percent_rate'        => 0,
                'fixed_rate'          => 300,
                'max_fee'             => 300,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createDefaultBankingPlan()
    {
        $this->addPricingRulesToDb(Models\Pricing\DefaultPlan::getBankingPlanData());
    }

    public function createStandardPlan($attributes = [])
    {
        $pricingPlanId = '1A0Fkd38fGZPVC';

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createZeroPricingplan()
    {
        $pricingPlanId = '10ZeroPricingP';

        $rows = [
            [
                'id'             => '1ZeroPricingR1',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR3',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR7',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR8',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'fund_account_validation',
                'payment_method' => 'bank_account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createEmiMerchantSubventionPlan()
    {
        $pricingPlanId = '1EmiSubPricing';

        $rows = [
            [
                'id'             => '1EmiSubPricing',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'EmiSubPricingP',
                'feature'        => 'emi',
                'payment_method' => 'card',
                'percent_rate'   => 549,
                'fixed_rate'     => 0,
                'emi_duration'   => 9,
                'payment_issuer' => 'ICIC',
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createEmiPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payment',
            'payment_method'      => 'emi',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 250,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createPayoutPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYz';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 50,
            'fixed_rate'          => 80,
            'org_id'              => '100000razorpay',
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createPricingPlanForDifferentOrg($orgId)
    {
        $pricingPlanId = '1hDYlICxbxOCYx';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 50,
            'fixed_rate'          => 80,
            'org_id'              => $orgId,
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createBankTransferMultiPricingPlan()
    {
        $pricingPlanId = 'btMultiPricing';

        $rows = [
            [
                'id'                  => 'BtPercentPrici',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 1600,
                'fixed_rate'          => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 10000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'BtPercentFlatP',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 10000,
                'amount_range_max'    => 1000000000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);

        return $pricingPlanId;
    }

    public function createDiwaliPromotionalPlan()
    {
        $pricingPlanId = 'BI7O6FmHlzLFZm';

         $rows = [
            [
                'id'                  => '1nvp2XPMxaRLxb',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpxOJaqpC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 100000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);

        return $pricingPlanId;
    }

    public function createPromotionalPlan()
    {
        $pricingPlanId = '1In3Yh5Mluj605';

         $rows = [
            [
                'id'             => '1AXp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1xsdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdx0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ptex2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ptex2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31xbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zxyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'                  => 'BtPzrcentPrici',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 1600,
                'fixed_rate'          => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 10000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'BtPexcentFlatP',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 10000,
                'amount_range_max'    => 1000000000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);

        return $pricingPlanId;
    }

    public function createTwoPercentPricingPlan($attributes = [])
    {
        $pricingPlanId = $attributes['plan_id'] ?? Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN;

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRPX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'type'           => 'pricing',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createImplicitPartnerPricingPlan($attributes = [])
    {
        $pricingPlanId = $attributes['plan_id'] ?? Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN;

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRQX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 180,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'type'           => 'pricing',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    protected function addPricingRulesToDb($rows)
    {
        $repo = new Models\Pricing\Repository;

        foreach ($rows as $row)
        {
            $pricing = new Models\Pricing\Entity;
            $pricing->fill($row);
            $repo->saveOrFail($pricing);
        }
    }
}
