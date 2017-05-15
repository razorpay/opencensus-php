<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Plan extends Base
{
    /**
     * Creates plan entity after creating a corresponding item entity first.
     *
     * @param array $planAttributes
     * @param array $itemAttributes
     *
     * @return \RZP\Models\Plan\Entity
     */
    public function create(
        array $planAttributes = [],
        array $itemAttributes = [])
    {
        $itemAttributes = array_merge(
            [
                'name'     => 'test plan',
                'amount'   => 2000,
                'currency' => 'INR',
            ],
            $itemAttributes);

        $item = $this->fixtures->item->createPlanType($itemAttributes);

        $planAttributes = array_merge(
                                $planAttributes,
                                ['item_id' => $item->getId()]);

        $plan = parent::create($planAttributes);

        return $plan;
    }
}
