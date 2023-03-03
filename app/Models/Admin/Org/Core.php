<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Core as MerchantCore;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = (new Entity)->generateId();

        $org->setAuditAction(Action::CREATE_ORG);

        $experimentResult = $this->isMerchantDashboardTimeoutExpEnabled();

        if ($experimentResult === false and
            isset($input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS]) === true and
            $input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS] !== Entity::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS)
        {
            $input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS] = Entity::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS;
        }

        $org->build($input);

        $this->repo->saveOrFail($org);

        if (isset($input['merchant_styles']) === true)
        {
            unset($input['merchant_styles']);
        }

        $this->addOrgRelatedEntities($org, $input);

        return $org;
    }

    public function fetch(string $orgId)
    {
        Entity::verifyIdAndStripSign($orgId);

        return $this->repo->org->findOrFailPublicWithRelations(
            $orgId, ['hostnames', 'permissions', 'workflow_permissions']);
    }

    public function edit(string $orgId, array $input)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $org->setAuditAction(Action::EDIT_ORG);

        $experimentResult = $this->isMerchantDashboardTimeoutExpEnabled($orgId);

        if ($experimentResult === false and
            isset($input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS]) === true and
            $input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS] !== Entity::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS)
        {
            $input[Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS] = Entity::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS;
        }

        if (empty($input[Entity::DEFAULT_PRICING_PLAN_ID]) === false)
        {
            // validate if the pricing id belongs to same org
            $this->repo->pricing->getPricingPlanByIdAndOrgId($input[Entity::DEFAULT_PRICING_PLAN_ID], $org->getId());
        }

        $org->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use($org, $input)
        {
            $this->repo->saveOrFail($org);

            $this->trace->info(TraceCode::ORG_LEVEL_2FA_CHANGES, [
                Entity::ORG_ID => $org->getId(),
                'input'        => $input
            ]);

            if (isset($input[Entity::PERMISSIONS]) === true)
            {
                $oldPerms = $org->permissions()->allRelatedIds()->toArray();

                // These perms are deleted from the organization
                $diffPerms = array_diff($oldPerms, $input[Entity::PERMISSIONS]);

                $this->deleteUnassignedPermissionsFromRoles($org, $diffPerms);

                $this->addOrgRelatedEntities($org, $input);
            }

            if (isset($input[Entity::WORKFLOW_PERMISSIONS]) === true)
            {
                $perms = $input[Entity::WORKFLOW_PERMISSIONS];

                $oldPerms = $this->repo->permission
                                       ->fetchAllByOrg($org->getId(), 'workflow')
                                       ->toArray();

                $oldPerms = array_map(function($perm) {
                    return $perm['id'];
                }, $oldPerms);

                $diffPerms = array_diff($oldPerms, $perms);

                $this->disableWorkflowPermissionsForOrg($org, $diffPerms);

                $this->enableWorkflowPermissionsForOrg($org, $perms);
            }

            if (isset($input['merchant_styles']) === true)
            {
                unset($input['merchant_styles']);
            }
        });

        $org = $this->fetch($org->getPublicId());

        return $org;
    }

    protected function isMerchantDashboardTimeoutExpEnabled($orgId = false)
    {
        $orgId = $orgId ? $orgId : UniqueIdEntity::generateUniqueId();

        $requestData = '{"org_id":"' . $orgId . '"}';

        $properties = [
            'id'            => $orgId,
            'experiment_id' => app('config')->get('app.enable_merchant_dashboard_timeout_experiment_id'),
            'request_data'  => $requestData,
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    protected function deleteUnassignedPermissionsFromRoles(Entity $org, array $diffPerms)
    {
        $orgId = $org->getPublicId();

        $roles = $this->repo->role->fetchByOrgId($orgId);

        // Laravel detach removes all the entities in pivot table if you send
        // empty array
        if (empty($diffPerms) === true)
        {
            return;
        }

        foreach ($roles as $role)
        {
            $this->repo->detach($role, 'permissions', $diffPerms);
        }
    }

    public function delete($id)
    {
        $id = Entity::verifyIdAndStripSign($id);

        $org = $this->repo->org->findOrFail($id);

        $org->setAuditAction(Action::DELETE_ORG);

        (new Hostname\Core)->deleteHostnamesOfOrg($id);

        // Required currently in 5.4 otherwise this function is not working.
        $org->flushEventListeners();

        $this->repo->org->deleteOrFail($org);

        return $org->toArrayDeleted();
    }

    /**
     * Adds permission to Org in both live and test.
     * @param Permission\Entity $permission
     * @param Entity $org
     */
    public function addPermissionToOrg(
        Permission\Entity $permission,
        Entity $org)
    {
        $this->repo->transactionOnLiveAndTest(function() use($permission, $org)
        {
            $permId = $permission->getId();

            $this->repo->attach($org, 'permissions', [$permId]);

            $role = $this->repo->role
                               ->getSuperAdminRoleByOrgId($org->getId());

            // attach newly added permission to superadmin.
            $this->repo->attach($role, 'permissions', [$permId]);
        });
    }

    public function deletePermissionFromOrg(
        Permission\Entity $permission,
        Entity $org)
    {
        $this->repo->transactionOnLiveAndTest(function() use($permission, $org)
        {

            $permId = $permission->getId();
            $orgId = $org->getId();

            $this->repo->detach($org, 'permissions', [$permId]);

            $roles = $this->repo->role->fetchByOrgId($orgId);

            foreach ($roles as $role)
            {
                $this->repo->detach($role, 'permissions', [$permId]);
            }
        });
    }

    protected function addOrgRelatedEntities(Entity $org, array $input)
    {
        if (isset($input[Entity::PERMISSIONS]) === true)
        {
            $this->repo->permission->validateExists($input[Entity::PERMISSIONS]);

            $this->repo->sync(
                $org, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);
        }

        if (isset($input[Entity::WORKFLOW_PERMISSIONS]) === true)
        {
            $this->enableWorkflowPermissionsForOrg(
                $org, $input[Entity::WORKFLOW_PERMISSIONS]);
        }
    }

    protected function enableWorkflowPermissionsForOrg(
        Entity $org,
        array $permissions)
    {
        $this->repo->permission->toggleWorkflowOnOrgForPermissions(
            $org->getId(), $permissions, true);
    }

    protected function disableWorkflowPermissionsForOrg(
        Entity $org,
        array $permissions)
    {
        $this->repo->permission->toggleWorkflowOnOrgForPermissions(
            $org->getId(), $permissions, false);
    }
}
