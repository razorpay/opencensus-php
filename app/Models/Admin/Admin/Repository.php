<?php

namespace RZP\Models\Admin\Admin;

use RZP\Base;

use RZP\Models\Merchant;
use RZP\Models\Admin\Role;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    // TODO Define the proxyfetch and admin fetch params

    public function getByUsername($username)
    {
        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->first();
    }

    public function retrieveByIdAndAdminIdOrFail($orgId, $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ID, '=', $adminId)
                    ->firstOrFail();
    }

    public function addRoleToAdmin(Admin\Entity $admin, Role\Entity $role)
    {
        $admin->roles()->save($role);
    }

    public function addMerchantToAdmin(
        Admin\Entity $admin,
        Merchant\Entity $merchant)
    {
        $admin->saveOrFailMerchant($merchant);
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

    public function hasRole(Admin\Entity $admin, Role\Entity $role)
    {
        // TODO Check how to do it with the pivot table. Iterating over a collection is bad!
        $roleId = $role->getId();

        return ! $admin->roles()->filter(function ($role) use($roleId)
            {
                return $role->getId() === $roleId;
            })->isEmpty();
    }

    public function hasMerchant(Admin\Entity $admin, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        return ! $admin->merchants->filter(
            function ($merchant) use($merchantId)
            {
                return $merchant->getId() === $merchantId;
            })->isEmpty();
    }
}
