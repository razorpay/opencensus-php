<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Plan extends Base
{
    public function create(array $planAttributes = [])
    {
        $this->fixtures->create('item', [
            'name' => 'test plan',
            'amount' => 2000,
            'currency' => 'INR',
            'type' => 'plan',
        ]);

        $plan = parent::create($planAttributes);

        return $plan;
    }
}
