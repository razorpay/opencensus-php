<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin;
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

        $group = $this->core->create($orgId, $input);

        return $group->toArrayPublic();
    }

    public function getGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        return $group->toArrayPublicWithRelationships();
    }

    public function deleteGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $data = $this->core->delete($orgId, $groupId);

        return $data;
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
            throw new Exception\BadRequestException('Admins not given in the url');
        }

        foreach ($input['admins'] as $adminId)
        {
            $adminId = Admin\Entity::verifyIdAndStripSign($adminId);

            $admin = $this->repo->admin->findOrFail($adminId);

            $this->repo->group->addAdminToGroup($group, $admin);
        }

        return $group->toArrayPublicWithRelationships();
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
