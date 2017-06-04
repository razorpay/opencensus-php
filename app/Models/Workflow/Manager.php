<?php

namespace RZP\Models\Workflow;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Admin;

/****
 * Workflow Manager manages all workflows and activities relating to it.
 *
 * It has functions which are universal to all the project's models not only of
 * workflows entity
 */

class Manager
{
    protected $app;

    protected $repo;

    protected $trace;

    protected $mode;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }

    public function getActionsForChecker(Admin\Entity $admin)
    {
        //
        // Get all the actions in the admin's org
        // Based on current level, get the steps/roles in the workflow
        // if the admin has the role, give the checker the action_id, step_id
        //

        $adminRoleIds = $admin->roles()->getRelatedIds()->toArray();

        $actions = $this->repo->workflow_action->findActionsForChecker($adminRoleIds);

        return $actions->toArrayPublic();
    }

    public function getActionsByMaker(Admin\Entity $admin)
    {
        $relations = ['workflow'];

        $actions = $this->repo->workflow_action->findByAdminIdAndOrgIdWithRelations(
            $admin->getId(), $admin->getOrgId(), $relations);

        return $actions;
    }

    public function getAllActionsByOrg(string $orgId)
    {
        $this->validateSuperAdminAccess();

        $actions = $this->repo->workflow_action->findByOrgId(
            $orgId);

        return $actions;
    }

    public function getClosedActionsByMaker(Admin\Entity $admin)
    {
        $actions = $this->repo->workflow_action
                              ->getClosedActionsByAdmin($admin->getId());

        return $actions;
    }

    public function getOpenActionsByOrg(string $orgId)
    {
        $this->validateSuperAdminAccess();

        $actions = $this->repo->workflow_action
                               ->findOpenActionsByOrgId($orgId);

        return $actions;
    }

    protected function validateSuperAdminAccess()
    {
        $admin = $this->app['basicauth']->getAdmin();

        if ($admin->isSuperAdmin() === false)
        {
            $data = ['admin_id' => $admin->getId()];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUPERADMIN_ACCESS_REQUIRED,
                null,
                $data);
        }
    }

    public function getActionsCheckedByAdmin()
    {
        $admin = $this->app['basicauth']->getAdmin();

        $actions = $this->repo->workflow_action
                              ->getActionsCheckedByAdmin($admin->getId());

        return $actions->toArrayPublic();
    }
}
