<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Checker;

class WorkflowController extends Controller
{
    protected $action;

    public function postCreateAction()
    {
        $input = Request::all();

        $result = (new Differ\Service)->create($input);

        return ApiResponse::json($result);
    }

    public function fetchDiffById(string $id)
    {
        $result = (new Differ\Service)->fetchDiffById($id);

        return ApiResponse::json($result);
    }

    public function postExecuteAction(string $id)
    {
        $input = Request::all();

        $action = (new Differ\Service)->fetchRequest($id);

        $routeParams = $action[Differ\Entity::ROUTE_PARAMS];

        $payload = $action[Differ\Entity::PAYLOAD];

        $controller = $action[Differ\Entity::CONTROLLER];

        $functionName = $action[Differ\Entity::FUNCTION_NAME];

        Request::replace($payload);

        $controller = App::make($controller);

        return App::call([$controller, $functionName], array_values($pathParams));
    }

    public function postActionChecker(string $id)
    {
        $input = Request::all();

        $data = (new Checker\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getActionCheckerMultiple(string $id)
    {
        $input = Request::all();

        $data = (new Checker\Service)->fetchMultiple($id, $input);
    }

    public function getActionChecker(string $id, string $checkerId)
    {
        $data = (new Checker\Service)->get($id, $checkerId);

        return ApiResponse::json($data);
    }
}
