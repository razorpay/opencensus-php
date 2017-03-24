<?php

namespace RZP\Models\Workflow;


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

    public function getActionsForAdmin($admin = null)
    {
        //
        // Get all the actions in the admin's org
        // Based on current level, get the steps/roles in the workflow
        // if the admin has the role, give the checker the action_id, step_id
        //

        if ($admin === null)
        {
            $admin = $this->app['basicauth']->getAdmin();
        }

        $actions = $this->repo->workflow_action->findByOrgId(
            $admin->getOrgId());

        $adminRoles = $admin->roles()->get(['id']);

        $adminRoles = array_map(function($role)
        {
            return $role->getId();

        }, $adminRoles);

        $actionsForAdmin = [];

        // TODO simplify the number of db calls by fetching in bulk and mapping
        // in memory
        foreach ($actions as $action)
        {
            $level = $action->getCurrentLevel();

            $steps = $this->repo->workflow_step->findByLevelAndWorkflowId(
                $level, $action->getWorkflowId(), ['role_id']);

            $actionRoles = [];

            foreach ($steps as $step)
            {
                $roleId = $step->getRoleId();

                if (in_array($roleId, $adminRoles, true) === true)
                {
                    $actionsForAdmin[] = [$action->getPublicId(), $step->getPublicId()];
                }
            }
        }

        return $actionsForAdmin;
    }

    public function getActionsByMaker($admin = null)
    {
        // if admin is not passed, accept admin as authAdmin
        if ($admin === null)
        {
            $admin = $this->app['basicauth']->getAdmin();
        }

        $actions = $this->repo->workflow_action->findByAdminIdAndOrgId(
            $admin->getId(), $admin->getOrgId());

        return $actions;
    }
}
