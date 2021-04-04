<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class PromotionEvent extends Base
{
    public function create(array $attributes = [])
    {
        $defaultValues = [
            'description'   => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $promotion = parent::create($attributes);

        return $promotion;
    }
}
