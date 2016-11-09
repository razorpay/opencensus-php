<?php

namespace RZP\Models\Admin\Group;

use RZP\Base;

use RZP\Models\Merchant;
use RZP\Models\Admin\Role;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'group';

    // TODO Define the proxyfetch and admin fetch params

    protected $proxyFetchParamRules = [
        Entity::ORG_ID  => 'sometimes|string',
        Entity::NAME    => 'sometimes|string',
    ];


    public function retrieveByIdAndGroupIdOrFail($orgId, $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ID, '=', $adminId)
                    ->firstOrFail();
    }

    public function addRoleToGroup(Group\Entity $group, Role\Entity $role)
    {
        $group->roles()->attach($role);
    }

    public function revokeRoleOrFail(Admin\Entity $admin, Role\Entity $role)
    {
        if ($this->hasRole($admin, $role) === true)
        {
            $admin->roles()->detach($role);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ROLE_NOT_ASSIGNED);
        }
    }

    public function hasRole(Group\Entity $group, Role\Entity $role)
    {
        // TODO Check how to do it with the pivot table. Iterating over a collection is bad!
        $roleId = $role->getId();

        return ! $group->roles()->filter(function ($role) use($roleId)
            {
                return $role->getId() === $roleId;
            })->isEmpty();
    }

    public function addMerchantToGroup(
        Group\Entity $admin,
        Merchant\Entity $merchant)
    {
        $group->merchants()->attach($merchant);
    }

    public function removeMerchantOrFail(
        Admin\Entity $admin,
        Merchant\Entity $merchant)
    {
        if ($this->hasMerchant($admin, $merchant) === true)
        {
            $admins->merchants()->detach($merchant);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ASSIGNED);
        }
    }

    public function hasMerchant(Group\Entity $group, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        return ! $group->merchants->filter(
            function ($merchant) use($merchantId)
            {
                return $merchant->getId() === $merchantId;
            })->isEmpty();
    }

    public function addAdminToGroup(Group\Entity $group, Admin\Entity $admin)
    {
        $group->admins()->attach($admin);
    }

    public function removeAdminFromGroup(
        Group\Entity $group,
        Admin\Entity $admin)
    {
        $group->admins()->detach($admin);
    }

    public function addSubGroup(Group\Entity $group, Group\Entity $subGroup)
    {
        $group->subGroups()->attach($subGroup);
    }

    public function removeSubGroup(Group\Entity $group, Group\Entity $subGroup)
    {
        $group->subGroups()->detach($subGroup);
    }

    public function fetchGroupsForOrg(string $orgId, array $input)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }
}
