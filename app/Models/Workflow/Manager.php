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

    public function getActionsByMaker(Admin\Entity $admin)
    {
        $relations = ['workflow', 'admin'];

        $actions = $this->repo->workflow_action->findByAdminIdAndOrgIdWithRelations(
            $admin->getId(), $admin->getOrgId(), $relations);

        return $actions;
    }



    public function getClosedActionsByMaker(Admin\Entity $admin)
    {
        $actions = $this->repo->workflow_action
                              ->getClosedActionsByAdmin(
                                  $admin->getId(), ['admin']);

        return $actions;
    }


    public function getActionsCheckedByAdmin()
    {
        $admin = $this->app['basicauth']->getAdmin();

        $actions = $this->repo->workflow_action
                              ->getActionsCheckedByAdmin(
                                  $admin->getId(), ['admin']);

        return $actions->toArrayPublic();
    }
}
