<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class AccessControlPrivileges extends Base
{
    public function create(array $attributes = [])
    {
        $defaultAttributes = [
            'name'        => 'Account Setting',
            'description' => 'A/c setting test description',
            'parent_id'   => null,
            'visibility'  => 1,
        ];
        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);;
    }
}
