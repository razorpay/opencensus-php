<?php

namespace RZP\Http\Middleware;

use Request;
use Closure;
use RZP\Http\Route;
use RZP\Trace\TraceCode;
use RZP\Events\DifferEvent;
use RZP\Models\Workflow\Action\Differ;
use Illuminate\Foundation\Application;

class Workflow
{
    const AUTH_HEADER = 'authorization';

    const CONTENT_TYPE = 'Content-Type';

    const USER_HEADER = 'x-dashboard-username';

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];

        $this->repo = $app['repo'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        $entity = $this->getEntityName($routeName);

        $entityId = $this->router->current()->getParameter('id');

        $input = $request->input();

        // In case of checker call, we have to unsset the action_id.
        // Also also verify whether its the correct action_id
        if (isset($input['action_id']) === true)
        {
            unset($input['action_id']);

            $request->replace($input);

            return $next($request);
        }

        if ($entity !== null)
        {
            $response = $this->repo->transactionDryRunOnLiveAndTest(function() use ($request, $next, $entity, $entityId)
            {
                $oldE = $this->repo->$entity->findByPublicId($entityId);

                $response = $next($request);

                $newE = $this->repo->$entity->findByPublicId($entityId);

                $diff = (new Differ\Service)->createDiff($oldE->toArray(), $newE->toArray());

                $event = $this->createDifferEvent($request, $diff, $entity, $entityId);

                if ($response->getStatusCode() === 200)
                {
                    event(new DifferEvent($event));
                }

                return $response;
            });
        }
        else
        {
            $response =  $next($request);
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

    private function createDifferEvent($request, $diff, $entity, $entityId)
    {
        $input = $request->input();

        $differEvent = [
            'entity_name' => $entity,
            'entity_id'   => $entityId,
            'actor'       => $request->header(self::USER_HEADER),
            'headers'     => [
                                self::AUTH_HEADER  => $request->header(self::AUTH_HEADER),
                                self::CONTENT_TYPE => $request->header(self::CONTENT_TYPE)
                            ],
            'type'        => Differ\Type::MAKER,
            'url'         => $request->getUri(),
            'method'      => $request->getMethod(),
            'payload'     => $input,
            'diff'        => $diff,
        ];

        return $differEvent;
    }
}
