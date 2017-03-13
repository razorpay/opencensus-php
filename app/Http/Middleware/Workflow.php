<?php

namespace RZP\Http\Middleware;

use Request;
use Closure;
use RZP\Http\Route;
use RZP\Models\Base\UniqueIdEntity;
use Illuminate\Foundation\Application;

class Workflow
{
    const AUTH_HEADER = 'authorization';

    const USER_HEADER = 'x-dashboard-username';

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        if ($this->isWorkflowRoute($routeName))
        {
            $this->modifyInput($request);
        }

        return $next($request);
    }

    private function isWorkflowRoute($routeName)
    {
        return (in_array($routeName, Route::$workflowRoutes, true) === true);
    }

    private function modifyInput($request)
    {
        if (isset($input['action_id']) === false)
        {
            $this->modifyMakerRequest($request);
        }
        else
        {
            $this->modifyCheckerRequest($request);
        }
    }

    private function modifyMakerRequest($request)
    {
        $input = $request->input();

        list($entity, $entityId) = $this->getEntity($request->getPathInfo());

        $makerRequest = [
            'type'     => 'maker',
            'uri'      => $request->getPathInfo(),
            'method'   => $request->getMethod(),
            'payload'  => $input,
            'headers'  => [ self:: AUTH_HEADER => $request->header(self::AUTH_HEADER) ],
            'actor'    => $request->header(self::USER_HEADER),
            'entity'   => $entity,
            'entityId' => $entityId
        ];

        $request->replace($makerRequest);
    }

    private function getEntity(string $uri)
    {
        $uriParams = explode('/', $uri);

        $entity = $uriParams[2];

        $entityId = $uriParams[3];

        if (UniqueIdEntity::verifyUniqueId($entityId, false) === 0)
        {
            $entityId = '';
        }

        return [$entity, $entityId];
    }

    private function modifyActionRequest($request)
    {
        $input = $request->input();

        unset($input['action_id']);

        $request->replace($input);
    }
}
