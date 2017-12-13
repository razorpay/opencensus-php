<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models;

class Pricing extends Base
{
    public function createDefaultPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

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
            ],
            [
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
                'percent_rate'        => 0,
                'fixed_rate'          => 0,
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createStandardPlan()
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
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1pteg2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
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
            ],
            [
                'id'             => '1ZeroPricingR2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1ZeroPricingR3',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1ZeroPricingR4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1ZeroPricingR5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
            ],
            [
                'id'             => '1ZeroPricingR6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
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
                'payment_issuer' => 'ICIC'
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
