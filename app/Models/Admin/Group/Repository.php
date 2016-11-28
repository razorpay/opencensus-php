<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant;
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


    public function retrieveByOrgIdAndIdOrFail(
        string $orgId,
        string $groupId)
    {
        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::ID, '=', $groupId)
                    ->with('admins')
                    ->with('merchants')
                    ->with('subGroups')
                    ->with('parents')
                    ->with('roles')
                    ->firstOrFail();
    }

    public function addRolesToGroup($roles, Entity $group)
    {
        $group->roles()->attach($roles);
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

    public function hasRole(Entity $group, Role\Entity $role)
    {
        // TODO Check how to do it with the pivot table. Iterating over a collection is bad!
        $roleId = $role->getId();

        $empty = $group->roles()->filter(
            function ($role) use($roleId)
            {
                return $role->getId() === $roleId;
            })
            ->isEmpty();

        return ($empty === false);
    }

    public function addMerchantsToGroup($merchants, Entity $group)
    {
        $group->merchants()->attach($merchants);
    }

    public function removeMerchantFromGroup(Entity $group, Merchant\Entity $merchant)
    {
        if ($this->hasMerchant($group, $merchant) === true)
        {
            $group->merchants()->detach($merchant);
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

    public function addAdminsToGroup($admins, Entity $group)
    {
        $group->admins()->attach($admins);
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

    public function fetchGroupsForOrg(string $orgId, array $input = array())
    {
        return $this->newQuery()
                    ->orgId($orgId)
                    // ->with('subGroups')
                    // ->with('parents')
                    ->get();
    }

    public function retrieveByIds(string $orgId, array $groupIds)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->whereIn(Entity::ID, $groupIds)
                    ->get();
    }

    public function validateOrgHasNoSuchGroup(Entity $group, Org\Entity $org)
    {
        $grpExists = $this->newQuery()
                          ->orgId($org->getId())
                          ->where(Entity::NAME, '=', $group->getName())
                          ->exists();

        if ($grpExists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The group with the name already exists');
        }
    }

}
