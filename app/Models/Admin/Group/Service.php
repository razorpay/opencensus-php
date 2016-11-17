<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Role;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createGroup(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        if (isset($input['admins']) === true)
        {
            $adminIds = [];

            foreach ($input['admins'] as $adminId)
            {
                $adminIds[] = Admin\Entity::verifyIdAndStripSign($adminId);
            }

            $input['admins'] = $adminIds;
        }

        if (isset($input['sub_groups']) === true)
        {
            $groupIds = [];

            foreach ($input['sub_groups'] as $groupId)
            {
                $groupIds[] = Entity::verifyIdAndStripSign($groupId);
            }

            $input['sub_groups'] = $groupIds;
        }

        if (isset($input['roles']) === true)
        {
            $roleIds = [];

            foreach ($input['roles'] as $roleId)
            {
                $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
            }

            $input['roles'] = $roleIds;
        }

        $group = $this->core->create($orgId, $input);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $group->getId());

        return $group->toArrayPublic();
    }

    public function getGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        return $group->toArrayPublic();
    }

    public function editGroup(string $orgId, string $groupId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        if (isset($input['admins']) === true)
        {
            $adminIds = [];

            foreach ($input['admins'] as $adminId)
            {
                $adminIds[] = Admin\Entity::verifyIdAndStripSign($adminId);
            }

            $input['admins'] = $adminIds;
        }

        if (isset($input['sub_groups']) === true)
        {
            $groupIds = [];

            foreach ($input['sub_groups'] as $subGroupId)
            {
                $groupIds[] = Entity::verifyIdAndStripSign($subGroupId);
            }

            $input['sub_groups'] = $groupIds;
        }

        if (isset($input['roles']) === true)
        {
            $roleIds = [];

            foreach ($input['roles'] as $roleId)
            {
                $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
            }

            $input['roles'] = $roleIds;
        }

        $group = $this->core->edit($orgId, $groupId, $input);

        return $group->toArrayPublic();
    }

    public function deleteGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $data = $this->core->delete($orgId, $groupId);

        return ['success' => true];
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $groups = $this->repo->group->fetchGroupsForOrg($orgId, $input);

        return $groups->toArrayPublic();
    }

    public function addRoleToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $roleIds = $input['role_ids'];
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        // Not using sync with Ids and roles
        foreach ($roleIds as $roleId)
        {
            $role = $this->repo->role->findOrFail($roleId);

            $this->repo->group->addRoleToGroup($group, $role);
        }
    }

    public function addMerchantsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $merchantIds = $input['merchant_ids'];

        // TODO Wrap it in a transaction or sync the m2m field
        // Check for merchantIds
        foreach ($merchantIds as $merchantId)
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $group = $this->repo->group->retrieveByOrgIdAndIdOrFail($orgId, $groupId);

            $this->repo->group->addMerchantToGroup($group, $merchant);
        }
    }

    public function addAdminsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $adminIds = [];

        if (isset($input['admins']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Admins not given in the input');
        }

        foreach ($input['admins'] as $adminId)
        {
            $adminId = Admin\Entity::verifyIdAndStripSign($adminId);

            $admin = $this->repo->admin->findOrFail($adminId);

            $this->repo->group->addAdminToGroup($group, $admin);
        }

        return $group->toArrayPublic();
    }

    public function revokeRoleFromGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);
        $roleId = Merchant\Entity::verifyIdAndStripSign($roleId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $role = $this->repo->role->findOrFail($roleId);

        $this->repo->group->revokeRoleOrFail($group, $role);
    }
}
