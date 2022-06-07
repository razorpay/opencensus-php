<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class Roles extends Base
{
    public function create(array $attributes = [])
    {

        $defaultAttributes = [
            'id'          => '1000customRole',
            'name'        => 'CustomRole',
            'description' => 'Test custom role',
            'type'        => 'custom',
            'created_by'  => 'test@razorpay.com',
            'updated_by'  => 'test@razorpay.com',
        ];
        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);
    }
}
