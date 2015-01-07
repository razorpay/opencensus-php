<?php

namespace Models\Pricing;

class DefaultPlan
{
    const PROMOTIONAL_PLAN_ID = '1In3Yh5Mluj605';
    const FULL_PLAN_ID = '1AXludj60w4pSp';

    public static function getPricingSeedData()
    {
        return array(
                array(
                    'id'            => '1GuENK6Hl2BWGx',
                    'plan_id'       => '1AXludj60w4pSp',
                    'plan_name'     => 'Full Price',
                    'payment_method'  => 'card',
                    'percent_rate'  => '290',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1GuENK6Xk6a8I0',
                    'plan_id'       => '1AXludj60w4pSp',
                    'plan_name'     => 'Full Price',
                    'payment_method'  => 'netbanking',
                    'percent_rate'  => '290',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1L8dUj9MzP3Bj3',
                    'plan_id'       => '1In3Yh5Mluj605',
                    'plan_name'     => 'Promotional Price',
                    'payment_method'  => 'card',
                    'percent_rate'  => '200',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1Nsi8IbQ3pWP7T',
                    'plan_id'       => '1In3Yh5Mluj605',
                    'plan_name'     => 'Promotional Price',
                    'payment_method'  => 'netbanking',
                    'percent_rate'  => '200',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    )
                );
    }
}