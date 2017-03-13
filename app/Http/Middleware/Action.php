<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use Request;
use RZP\Http\Route;
use RZP\Models\Admin;
use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\ErrorCode;

class Action
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
        $this->modifyInput($request);

        return $next($request);
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

        $makerRequest = [];

        $makerRequest['type'] = 'maker';

        $makerRequest['uri'] = $request->getPathInfo();

        $makerRequest['method'] = $request->getMethod();

        $makerRequest['payload'] = $input;

        $makerRequest['headers'] = [self:: AUTH_HEADER => $request->header(self::AUTH_HEADER)];

        $makerRequest['actor'] = $request->header(self::USER_HEADER);

        list($entity, $entityId) = $this->getEntity($request->getPathInfo());

        $makerRequest['entity'] = $entity;

        $makerRequest['entityId'] = $entityId;

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
