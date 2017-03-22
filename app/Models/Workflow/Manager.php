<?php

namespace RZP\Models\Workflow;

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

    public function getActionsForAdmin()
    {
        // Get all the actions in the admin

        // Based on current level, get the steps/roles in the workflow

        // if the admin has the role, give the checker the action_id, step_id
    }
}
