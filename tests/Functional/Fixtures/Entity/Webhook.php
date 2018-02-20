<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Webhook extends Base
{
    public function create(array $attributes = array())
    {
        $defaultAttributes = array('events' => ['payment.authorized' => '1']);

        $attributes = array_merge($attributes, $defaultAttributes);

        return parent::create($attributes);
    }
}