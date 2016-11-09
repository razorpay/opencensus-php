<?php

namespace RZP\Models\Admin\Admin;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Role;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    // TODO Define the proxyfetch and admin fetch params
    public function findOrFailByUsername($username)
    {
        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->firstOrFail();
    }

    public function retrieveByIdAndAdminIdOrFail($orgId, $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ID, '=', $adminId)
                    ->firstOrFail();
    }

    public function addRoleToAdmin(Entity $admin, Role\Entity $role)
    {
        $admin->roles()->attach($role);
    }

    public function addMerchantToAdmin(
        Entity $admin,
        Merchant\Entity $merchant)
    {
        return $admin->saveOrFailMerchant($merchant);
    }

    public function removeMerchantOrFail(
        Entity $admin,
        Merchant\Entity $merchant)
    {
        if ($this->hasMerchant($admin, $merchant) === true)
        {
            return $admins->merchants()->detach($merchant);
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_NOT_ASSIGNED);
    }

    public function revokeRoleOrFail(Entity $admin, Role\Entity $role)
    {
        if ($this->hasRole($admin, $role) === true)
        {
            return $admin->roles()->detach($role);
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ROLE_NOT_ASSIGNED);
    }

    public function hasRole(Entity $admin, Role\Entity $role)
    {
        // TODO Check how to do it with the pivot table. Iterating over a collection is bad!
        $roleId = $role->getId();

        $isEmpty = $admin->roles()->filter(function($role) use ($roleId)
        {
            return ($role->getId() === $roleId);
        })->isEmpty();

        return ($isEmpty === false);
    }

    public function hasMerchant(Entity $admin, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $isEmpty = $admin->merchants->filter(function($merchant) use ($merchantId)
        {
            return ($merchant->getId() === $merchantId);
        })->isEmpty();

        return ($isEmpty === false);
    }

    public function fetchAdminsForOrg(string $orgId, array $input)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }
}
