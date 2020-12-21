<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class SubscriptionOffer extends Base
{
    public function create(array $attributes = [])
    {
        $start = Carbon::now()->getTimestamp();
        $end = Carbon::now()->addYear(1)->getTimestamp();

        $defaultValues = [

        ];

        $attributes = array_merge($defaultValues, $attributes);

        $offer = parent::create($attributes);

        return $offer;
    }
}
