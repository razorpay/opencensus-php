<?php

namespace RZP\Http\Middleware;

use App;
use Request;
use Closure;
use RZP\Http\Route;
use RZP\Models\Workflow\Action\Differ;
use Illuminate\Foundation\Application;

class Workflow
{
    const USER_HEADER = 'X-Dashboard-Username';

    const WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\WorkflowController';

    protected $app;

    protected $config;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->config = $this->app['config'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        // Middleware is only used for workflow routes
        if (in_array($routeName, array_keys(Route::$workflowRoutes), true) === false)
        {
            return $next($request);
        }

        $entity = $this->getEntityName($routeName);

        if (($this->config->get('database.es_workflow_action_mock') === false) and
            ($entity !== null))
        {
            $routeParams = $this->router->current()->parameters();

            $entityId = array_values($routeParams)[0];

            $params = $this->createMakerEntity($request, $entity, $entityId);

            $request->replace($params);

            return App::make(self::WORKFLOW_CONTROLLER)->postCreateAction();
        }
        else
        {
            $response = $next($request);
        }

        return $response;
    }

    private function getEntityName($routeName)
    {
        $entity = null;

        if (isset(Route::$workflowRoutes[$routeName]) === true)
        {
            $entity = Route::$workflowRoutes[$routeName];
        }

        return $entity;
    }

    private function createMakerEntity($request, $entity, $entityId)
    {
        $input = $request->input();

        $routeName = $this->router->currentRouteName();

        $controller = $this->router->currentRouteAction();

        $routeParams = $this->router->current()->parameters();

        $differEntity = [
           Differ\Entity::ENTITY_NAME  => $entity,
           Differ\Entity::ENTITY_ID    => $entityId,
           Differ\Entity::ACTOR        => $request->header(self::USER_HEADER),
           Differ\Entity::TYPE         => Differ\Type::MAKER,
           Differ\Entity::URL          => $request->getUri(),
           Differ\Entity::ROUTE_PARAMS => $routeParams,
           Differ\Entity::METHOD       => $request->getMethod(),
           Differ\Entity::PAYLOAD      => $input,
           Differ\Entity::CONTROLLER   => $controller,
           Differ\Entity::ROUTE        => $routeName,
        ];

        return $differEntity;
    }
}
