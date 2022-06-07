<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class AccessPolicyAuthzRolesMap extends Base
{
    public function create(array $attributes = [])
    {
        $defaultAttributes = [
            'privilege_id'  => '10000privilege',
            'action'        => 'read',
            'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'meta_data'     => [
                'tooltip'       => 'Tooltip',
                'description'   => 'Access policy test description',
            ],
        ];


        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);;
    }
}
