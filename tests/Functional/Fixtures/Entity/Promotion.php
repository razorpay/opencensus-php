<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class Promotion extends Base
{
    public function createOnetime(array $attributes = [])
    {
        $promotionAttributes = [
            'iterations'     => '1',
            'credits_expire' => false,
        ];

        $attributes = array_merge($promotionAttributes, $attributes);

        $promotion = $this->fixtures->create('promotion', $attributes);

        return $promotion;
    }

    public function createRecurring(array $attributes = [])
    {
        $schedule = $this->fixtures->create('schedule', $attributes);

        $promotionAttributes = [
            'iterations'     => '1',
            'credits_expire' => true,
            'schedule_id'    => $schedule->getId(),
        ];

        $attributes = array_merge($promotionAttributes, $attributes);

        $promotion = $this->fixtures->create('promotion', $attributes);

        return $promotion;
    }

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'name'        => 'Test Promotion',
            'amount'      => 100,
            'credit_type' => 'fee',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $promotion = parent::create($attributes);

        return $promotion;
    }
}
