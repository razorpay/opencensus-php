<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models;

class Pricing extends Base
{
    public function createDefaultPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $rows = array(
                    array(
                        'id'                  => '1nvp2XPMmaRLxb',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'card',
                        'payment_method_type' => null,
                        'payment_network'     => null,
                        'payment_issuer'      => null,
                        'percent_rate'        => 200,
                        'fixed_rate'          => 0,
                        'international'       => 0,
                    ),
                    array(
                        'id'                  => '1OwH8rTI0ejFxS',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'card',
                        'payment_method_type' => null,
                        'payment_network'     => 'AMEX',
                        'payment_issuer'      => null,
                        'percent_rate'        => 300,
                        'fixed_rate'          => 0,
                        'international'       => 0,
                    ),
                    array(
                        'id'                  => '1fq0OXpgeyafQq',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'card',
                        'payment_method_type' => null,
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => null,
                        'percent_rate'        => 300,
                        'fixed_rate'          => 0,
                        'international'       => 0,
                    ),
                    array(
                        'id'                  => '1nwo5YENadEFvf',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'card',
                        'payment_method_type' => null,
                        'payment_network'     => null,
                        'payment_issuer'      => null,
                        'percent_rate'        => 200,
                        'fixed_rate'          => 0,
                        'international'       => 1,
                    ),
                    array(
                        'id'                  => '1zD0BXpeOyaqpB',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'netbanking',
                        'payment_method_type' => null,
                        'payment_network'     => null,
                        'payment_issuer'      => null,
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => 0,
                    ),
                    array(
                        'id'                  => '1zE3CYqf1zbyrD',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'wallet',
                        'payment_method_type' => null,
                        'payment_network'     => null,
                        'payment_issuer'      => null,
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => 0,
                    ),
                    array(
                        'id'                  => '1zE3CYqf1zbyaE',
                        'plan_id'             => '1hDYlICobzOCYt',
                        'plan_name'           => 'testDefaultPlan',
                        'feature'             => 'payment',
                        'payment_method'      => 'emi',
                        'payment_method_type' => null,
                        'payment_network'     => null,
                        'payment_issuer'      => null,
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                    ),
                    array(
                        'id' => '1zE3CYqf1zbyaF',
                        'plan_id' => '1hDYlICobzOCYt',
                        'plan_name' => 'testDefaultPlan',
                        'feature'   =>  'payment',
                        'payment_method' => 'upi',
                        'payment_method_type' => null,
                        'payment_network' => null,
                        'payment_issuer' => null,
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                    ),

                );

        $this->addPricingRulesToDb($rows);

        // $pricing = $this->repo->getPricingPlanByIdOrFailPublic($pricingPlanId);
    }

    public function createStandardPlan()
    {
        $pricingPlanId = '1A0Fkd38fGZPVC';

        $rows = array(
                    array(
                        'id'             => '1ABp2Xd3t5aRLX',
                        'plan_id'        => '1A0Fkd38fGZPVC',
                        'plan_name'      => 'standard_plan',
                        'feature'        => 'payment',
                        'payment_method' => 'card',
                        'percent_rate'   => 2000,
                        'fixed_rate'     => 0,
                    ),
                    array(
                        'id'             => '1osdf0GGDdalfF',
                        'plan_id'        => '1A0Fkd38fGZPVC',
                        'plan_name'      => 'standard_plan',
                        'feature'        => 'payment',
                        'payment_method' => 'netbanking',
                        'percent_rate'   => 2000,
                        'fixed_rate'     => 0,
                    ),
                    array(
                        'id'             => '1pteg2HHEebmhH',
                        'plan_id'        => '1A0Fkd38fGZPVC',
                        'plan_name'      => 'standard_plan',
                        'feature'        => 'payment',
                        'payment_method' => 'wallet',
                        'percent_rate'   => 2000,
                        'fixed_rate'     => 0,
                    ),
                );

        $this->addPricingRulesToDb($rows);

//        $pricing = $repo->getPricingPlanByIdOrFailPublic($pricingPlanId);
    }

    public function createZeroPricingplan()
    {
        $pricingPlanId = '10ZeroPricingP';

        $rows = array(
                    array(
                        'id'             => '1ZeroPricingR1',
                        'plan_id'        => '10ZeroPricingP',
                        'plan_name'      => 'ZeroPricingPlan',
                        'feature'        => 'payment',
                        'payment_method' => 'card',
                        'percent_rate'   => 0,
                        'fixed_rate'     => 0,
                    ),
                    array(
                        'id'             => '1ZeroPricingR2',
                        'plan_id'        => '10ZeroPricingP',
                        'plan_name'      => 'ZeroPricingPlan',
                        'feature'        => 'payment',
                        'payment_method' => 'netbanking',
                        'percent_rate'   => 0,
                        'fixed_rate'     => 0,
                    ),
                    array(
                        'id'             => '1ZeroPricingR3',
                        'plan_id'        => '10ZeroPricingP',
                        'plan_name'      => 'ZeroPricingPlan',
                        'feature'        => 'payment',
                        'payment_method' => 'wallet',
                        'percent_rate'   => 0,
                        'fixed_rate'     => 0,
                    ),
                );

        $this->addPricingRulesToDb($rows);
    }

    protected function addPricingRulesToDb($rows)
    {
        $repo = new Models\Pricing\Repository;

        $modes = ['live', 'test'];

        // foreach ($modes as $mode)
        // {
        //     $this->connection($mode);

            foreach ($rows as $row)
            {
                $pricing = new Models\Pricing\Entity;
                $pricing->fill($row);
                $repo->saveOrFail($pricing);
            }
        // }
   }
}
