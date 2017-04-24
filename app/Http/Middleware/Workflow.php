<?php

namespace RZP\Http\Middleware;

use App;
use Request;
use Closure;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Service as WorkflowService;
use Illuminate\Foundation\Application;

class Workflow
{
    const WILDCARD_PERMISSION = '*';

    const WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\WorkflowController';

    protected $app;

    protected $config;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->config = $this->app['config'];

        $this->router = $app['router'];

        $this->ba = $this->app['basicauth'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        // Disable workflows if:
        // - It is mocked
        // - There's no admin user in current context. This means
        // that the route might be running under proxy/app without
        // any admin context
        if (($this->config->get('heimdall.workflows.mock') === true) or
            ($this->ba->isAdmin() !== true))
        {
            return $next($request);
        }

        try
        {
            $permissions = $this->getRoutePermissions($routeName);

            $admin = $this->ba->getAdmin();

            $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
                $permissions, $admin->getOrgId());

            // If the permissions has no workflow assigned to it
            // then let's not apply any maker-checker process
            if ($permissionHasWorkflow === false)
            {
                return $next($request);
            }
        }
        catch(Exception\BadRequestException $ex)
        {
            // This middleware will only run for routes whose
            // permission have a workflow defined for them.
            if ($ex->getCode() === ErrorCode::BAD_REQUEST_PERMISSION_ERROR)
            {
                return $next($request);
            }
            else
            {
                throw $ex;
            }
        }

        // Since we need to calculate the diffs, we'll need
        // the main entity being acted upon by the route
        // that's going to be executed. This is not entirely
        // fool-proof but will work well for a good number of
        // our routes (MVP acceptable).

        $entity = Route::$workflowRoutes[$routeName] ?? null;

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
        $params = $this->createDifferEntity($request, $entity, $entityId);

        // Replace Input for the current request
        $request->replace($params);

        return App::make(self::WORKFLOW_CONTROLLER)->postWorkflowAction();
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

        $permissions = $this->getRoutePermissions($routeName);

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
           Differ\Entity::PERMISSIONS  => $permissions,
        ];

        return $differEntity;
    }

    private function getRoutePermissions($routeName)
    {
        $adminAuthRoutes = Route::$routePermission;

        if ((isset($adminAuthRoutes[$routeName]) === false) or
            (in_array(self::WILDCARD_PERMISSION, $adminAuthRoutes[$routeName], true) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PERMISSION_ERROR);
        }

        return $adminAuthRoutes[$routeName];
    }

}
