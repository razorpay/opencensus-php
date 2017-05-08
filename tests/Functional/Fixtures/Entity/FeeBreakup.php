<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class FeeBreakup extends Base
{
    public function create(array $attributes = array())
    {
        $defaultValues = array(
            'created_at' => time() - 10,
            'updated_at' => time() - 5,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $feeBreakup = parent::create($attributes);

        return $feeBreakup;
    }
}
