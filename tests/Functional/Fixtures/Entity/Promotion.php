<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Schedule\Anchor;
use RZP\Models\Schedule\Period;

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

    public function createOnetimeDaily(array $attributes = [])
    {
        $scheduleAttributes = [
            'period' => Period::DAILY,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promotionAttributes = [
            'credits_expire' => true,
            'schedule_id'    => $schedule->getId(),
        ];

        $attributes = array_merge($promotionAttributes, $attributes);

        $promotion = $this->fixtures->create('promotion', $attributes);

        return $promotion;
    }

    public function createRecurring(array $attributes = [])
    {
        $currentTime = Carbon::now(Timezone::IST);

        $anchor = Anchor::getAnchor(Period::MONTHLY, $currentTime);

        $scheduleAttributes = [
            'period' => Period::MONTHLY,
            'anchor' => $anchor,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promotionAttributes = [
            'iterations'     => '1',
            'credits_expire' => true,
            'schedule_id'    => $schedule->getId(),
        ];

        $attributes = array_merge($promotionAttributes, $attributes);

        $promotion = $this->fixtures->create('promotion', $attributes);

        return $promotion;
    }

    public function createBankingPromotion(array $attributes = [])
    {
        $defaultValues = [
            'product'       => 'banking',
            'credit_type'   => 'reward_fee',
            'purpose'       => 'test sign up promotion'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $promotion = $this->fixtures->create('promotion', $attributes);

        return $promotion;
    }

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'name'          => 'Test-Promotion',
            'credit_amount' => 100,
            'credit_type'   => 'amount',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $promotion = parent::create($attributes);

        return $promotion;
    }
}
