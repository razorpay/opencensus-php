<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class AccessControlHistoryLogs extends Base
{
    public function create(array $attributes = [])
    {
        $defaultAttributes = [
            'entity_id'     => '10AccessPolicy',
            'entity_type'   => 'access_policy_authz_roles_map',
            'message'       => 'Test history log',
            'previous_value'=> null,
            'new_value'     => [
                'privilege_id'  => '10000privilege',
                'action'        => 'read',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'meta_data'     => [
                    'tooltip'       => 'Tooltip',
                    'description'   => 'Access policy test description',
                ],
            ],
        ];
        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);
    }
}
