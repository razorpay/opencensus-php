<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Comment;

class WorkflowController extends Controller
{
    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->differ = new Differ\Service;

        $this->comment = new Comment\Service;
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

        $entityId = $action[Differ\Entity::ENTITY_ID];

        $payload = $action[Differ\Entity::PAYLOAD];

        $controller = $action[Differ\Entity::CONTROLLER];

        $functioName = $action[Differ\Entity::FUNCTION_NAME];

        Request::replace($payload);

        return App::make($controller)->$functioName($entityId);
    }

    public function postCreateActionComment(string $actionId)
    {
        $input = Request::all();

        $result = $this->comment->create($actionId, $input);

        return ApiResponse::json($result);
    }

    public function getActionComments(string $actionId)
    {
        $result = $this->comment->fetchByActionId($actionId);

        return ApiResponse::json($result);
    }
}
