<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Comment;
use RZP\Models\Workflow\Action\Checker;

class WorkflowController extends Controller
{
    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->differ = new Differ\Service;
    }

    public function postCreateAction()
    {
        $input = Request::all();

        $result = $this->differ->create($input);

        return ApiResponse::json($result);
    }

    public function fetchDiffById(string $id)
    {
        $result = $this->differ->fetchDiffById($id);

        return ApiResponse::json($result);
    }

    public function postExecuteAction(string $id)
    {
        $input = Request::all();

        $action = $this->differ->fetchRequest($id);

        $pathParams = $action[Differ\Entity::PATH_PARAMS];

        $payload = $action[Differ\Entity::PAYLOAD];

        $controller = $action[Differ\Entity::CONTROLLER];

        $functionName = $action[Differ\Entity::FUNCTION_NAME];

        Request::replace($payload);

        $controller = App::make($controller);

        return call_user_func_array([$controller, $functionName], array_values($pathParams));
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

    public function postActionComment(string $actionId)
    {
        $input = Request::all();

        $result = (new Comment\Service)->create($actionId, $input);

        return ApiResponse::json($result);
    }

    public function getActionComments(string $actionId)
    {
        $result = (new Comment\Service)->fetchByActionId($actionId);

        return ApiResponse::json($result);
    }
}
