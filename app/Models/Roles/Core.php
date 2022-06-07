<?php

namespace RZP\Models\Roles;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listRolesForMerchant($input)
    {
        $this->setInputParamForListRoles($input);

        $roles = $this->repo->roles->listRoles($input);

        $roleIds = $roles
            ->pluck(Entity::ID)
            ->toArray();

        $rolesGroupedByType = $roles
            ->groupBy(Entity::TYPE)
            ->toArray();

        $userCount = $this->repo->merchant_user->getUserCountByMerchantIdAndRoleId($this->merchant->getId(), $roleIds)->groupBy('role')->toArray();

        foreach ($rolesGroupedByType as & $roles)
        {
            foreach ($roles as & $role)
            {
                if (empty($userCount[$role[Entity::ID]]) === false)
                {
                    $role[Entity::MEMBERS] = $userCount[$role[Entity::ID]][0]['count'];
                }
                else
                {
                    $role[Entity::MEMBERS] = 0;
                }
            }
        }

        return $rolesGroupedByType;
    }

    protected function setInputParamForListRoles(& $input)
    {
        if (isset($input[Entity::TYPE]) === false)
        {
            $input[Entity::TYPE] = [
                Entity::CUSTOM,
                Entity::STANDARD
            ];
        }
        else
        {
            $input[Entity::TYPE] = [$input[Entity::TYPE]];
        }

        if (isset($input[Entity::ID]) === true)
        {
            Entity::verifyIdAndStripSign($input[Entity::ID]);
        }
    }
}
