<?php

namespace RZP\Services\Workflow;

use RZP\Exception;
use RZP\Http\Route;
use Illuminate\Support\Facades\App;
use RZP\Models\Workflow\Action;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\State;
use RZP\Exception\EarlyWorkflowResponse;
use RZP\Models\Workflow\Service as WorkflowService;

class Service
{
    const WILDCARD_PERMISSION = '*';

    const WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\WorkflowController';

    const EXECUTE_ROUTE_NAME = 'action_request_execute';

    protected $router;

    protected $entity;

    protected $config;

    protected $permission;

    protected $diff = [];

    protected $oldEntity;

    protected $newEntity;

    public function __construct($app)
    {
        $this->app = $app;

        $this->router = $this->app['router'];

        $this->config = $this->app['config'];

        $this->request = $this->app['request'];

        $this->ba = $this->app['basicauth'];
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function trigger()
    {
        // Since we need to calculate the diffs, we'll need
        // the main entity being acted upon by the route
        // that's going to be executed. This is not entirely
        // fool-proof but will work well for a good number of
        // our routes (MVP acceptable).

        $entity = $this->entity;

        if (empty($entity))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ENTITY_NOT_FOUND);
        }

        $routeParams = $this->router->current()->parameters();

        // Pick the `id` first, if not then the first value
        // First value is not entirely robust though
        $entityId = $routeParams['id'] ?? array_values($routeParams)[0];

        // Check if any actions are in open/approved state on the same
        // entity. If yes then prevent any further operations on this.
        (new Action\Validator)->validateLiveActionsOnEntity($entity, $entityId);

        // Necessary data to pass to WorkflowController
        $params = $this->createDifferEntity($this->request, $entity, $entityId);

        // returns Workflow\Action\Entity->toArrayPublic()
        $data = (new Action\Service)->create($params);

        return $data;
    }

    /*
        Resolve a bunch of data points through which we can
        compute a diff as well as later execute the actual
        action once all the checkers have approved this
        incoming request.
    */
    private function createDifferEntity($request, $entity, $entityId)
    {
        $input = $request->input();

        $routeName = $this->router->currentRouteName();

        $controller = $this->router->currentRouteAction();

        $routeParams = $this->router->current()->parameters();

        $permission = $this->getPermission();

        $admin = $this->ba->getAdmin();

        $maker = $admin->getName() ?? $admin->getUsername() ?? $admin->getEmail();

        $differEntity = [
            Differ\Entity::ENTITY_NAME  => $entity,
            Differ\Entity::ENTITY_ID    => $entityId,
            Differ\Entity::ADMIN_ID     => $admin->id,
            Differ\Entity::MAKER        => $maker,
            Differ\Entity::TYPE         => Differ\Type::MAKER,
            Differ\Entity::URL          => $request->getUri(),
            Differ\Entity::ROUTE_PARAMS => $routeParams,
            Differ\Entity::METHOD       => $request->getMethod(),
            Differ\Entity::PAYLOAD      => $input,
            Differ\Entity::STATE        => State\Entity::OPEN,
            Differ\Entity::CONTROLLER   => $controller,
            Differ\Entity::ROUTE        => $routeName,
            Differ\Entity::PERMISSION   => $permission,
        ];

        $diff = $this->getDiff();

        if (empty($diff) === false)
        {
            $differEntity[Differ\Entity::DIFF] = $diff;
        }

        return $differEntity;
    }

    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function setDiff($diff)
    {
        $this->diff = $diff;
    }

    public function getDiff()
    {
        return $this->diff;
    }

    public function setOldEntity($entity)
    {
        $this->oldEntity = $entity;

        return $this;
    }

    public function getOldEntity()
    {
        return $this->oldEntity;
    }

    public function setNewEntity($entity)
    {
        $this->newEntity = $entity;

        return $this;
    }

    public function getNewEntity()
    {
        return $this->newEntity;
    }

    protected function permissionHasWorkflow()
    {
        $permission = $this->getPermission();

        if (empty($permission) === true)
        {
            return false;
        }

        $admin = $this->ba->getAdmin();

        $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
            $permission, $admin->getOrgId());

        return $permissionHasWorkflow;
    }

    /*
        Can be used like this:

        Workflow::setPermission($permission)
                ->handle($entity, function ($entity) {
                    // Execute business logic on the $entity
                })

        $entity is supposed to be the main entity on which
        diff will be computed pre callback execution and post
        callback execution which will change the $entity due to
        the business logic code.
    */
    public function handle($entity = null, $callback = null)
    {
        // 1. If the permission has no workflow then don't do anything
        // 2. If this is an execute call, then return as well
        //
        if (($this->permissionHasWorkflow() === false) or
            ($this->config->get('heimdall.workflows.mock') === true) or
            ($this->app['api.route']->isWorkflowExecuteCall() === true))
        {
            // Run the callback though, as it might have business
            // specific logic actually required for execution.
            if (($entity !== null) and ($callback !== null))
            {
                $callback($entity);
            }

            return;
        }

        // Instantiate code for diff creation
        $differCore = new Differ\Core;

        $oldEntity = null;

        $newEntity = null;

        if (($entity === null) and ($callback === null))
        {
            $oldEntity = $this->getOldEntity();

            $newEntity = $this->getNewEntity();
        }
        else
        {
            $oldEntity = clone $entity;

            $callback($entity);

            // If the callback modifies the $entity then
            // we will have $newEntity due to call by reference!
            $newEntity = $entity;
        }

        // Set entity
        $this->setEntity($newEntity->getEntityName());

        $diff = $differCore->createDiff(
            $oldEntity->toArray(), $newEntity->toArray());

        $this->setDiff($diff);

        // Trigger the entite maker/checker (workflow) flow
        $workflowAction = $this->trigger();

        $workflowAction = json_encode($workflowAction);

        // When there's a workflow action throw an exception
        // to abort further flow of code (services, controllers, etc.)
        throw new EarlyWorkflowResponse(
            200,
            $workflowAction,
            null,
            ['Content-Type' => 'application/json']
        );
    }
}
