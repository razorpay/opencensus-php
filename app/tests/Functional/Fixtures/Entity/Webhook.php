<?php

namespace Tests\Functional\Fixtures\Entity;

class Payment extends Base
{
    public function create(array $attributes = array())
    {
        $defaultAttributes = array('events' => ['payment.authorized' => true]);

        $attributes = array_merge($attributes, $defaultAttributes);

        return parent::create($attributes);
    }
}