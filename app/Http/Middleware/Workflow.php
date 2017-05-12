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
use RZP\Models\Workflow\Action\Differ\EntityValidator;

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
            $permission = $this->getRoutePermission($routeName);

            $admin = $this->ba->getAdmin();

            $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
                $permission, $admin->getOrgId());

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

        $entity = EntityValidator::getEntityName($routeName);

        $this->app['workflow']
             ->setEntity($entity)
             ->setPermission($permission);

        return $this->app['workflow']->trigger();
    }

    private function getRoutePermission($routeName)
    {
        $routePermissionList = Route::$routePermission;

        if (empty($routePermissionList[$routeName]) === false)
        {
            $permission = $routePermissionList[$routeName];

            // Required permission cannot be wildcard
            // since we won't apply workflows on wildcards
            if ($permission !== self::WILDCARD_PERMISSION)
            {
                return $permission;
            }
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PERMISSION_ERROR);
    }

}
