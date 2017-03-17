<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Models\Workflow\Action\Differ;

class WorkflowController extends Controller
{
    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->differService = new Differ\Service;
    }

    public function postCreateAction()
    {
        $input = Request::all();

        $result = $this->differService->create($input);

        return ApiResponse::json($result);
    }

    public function fetchDiffById(string $id)
    {
        $result = $this->differService->fetchDiffById($id);

        return ApiResponse::json($result);
    }

    public function postExecuteAction(string $id)
    {
        $input = Request::all();

        $action = $this->differService->fetchRequest($id);

        $entityId = $action[Differ\Entity::ENTITY_ID];

        $payload = $action[Differ\Entity::PAYLOAD];

        $controller = $action[Differ\Entity::CONTROLLER];

        $functionName = $action[Differ\Entity::FUNCTION_NAME];

        Request::replace($payload);

        return App::make($controller)->$functionName($entityId);
    }
}
