<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class EmiPlan extends Base
{
    protected $items = array(
        array(
            'id'                => '10101010101010',
            'duration'          => '9',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '300000',
        ),
        array(
            'id'                => '10101010101011',
            'duration'          => '3',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'UTIB',
            'min_amount'        => '300000',
        ),
        array(
            'id'                => '10101010101100',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'KKBK',
            'min_amount'        => '300000',
        ),
    );

    public function createDefaultEmiPlans()
    {
        $items = $this->items;

        $emiPlans = [];
        foreach ($items as $attributes)
        {
            $emiPlans[] = $this->fixtures->create('emi_plan', $attributes);
        }

        return $emiPlans;
    }
}
