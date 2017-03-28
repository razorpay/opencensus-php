<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\Workflow;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Comment;
use RZP\Models\Workflow\Action\Checker;

class WorkflowController extends Controller
{
    public function postActionDiff(string $id)
    {
        $input = Request::all();

        $result = (new Differ\Service)->create($id, $input);

        return ApiResponse::json($result);
    }

    public function getActionDiff(string $id)
    {
        $result = (new Differ\Service)->get($id);

        return ApiResponse::json($result);
    }

    public function postWorkflowAction()
    {
        $input = Request::all();

        $data = (new Action\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function postExecuteAction(string $id)
    {
        $input = Request::all();

        // Do not execute the action if it is not approved by all checkers
        $isActionApproved (new Action\Core)->checkAndMarkActionApproved($action);

        if ($isActionApproved === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_NOT_APPROVED);
        }

        $diff = (new Differ\Service)->fetchRequest($id);

        $routeParams = $diff[Differ\Entity::ROUTE_PARAMS];

        $payload = $diff[Differ\Entity::PAYLOAD];

        $controller = $diff[Differ\Entity::CONTROLLER];

        $functionName = $diff[Differ\Entity::FUNCTION_NAME];

        Request::replace($payload);

        $controller = App::make($controller);

        $response = App::call([$controller, $functionName], array_values($routeParams));

        $state = State\Entity::EXECUTED;

        if ($response->getStatusCode() !== 200)
        {
            $state = State\Entity::FAILED;
        }

        (new Differ\Service)->changeActionState($id, $state);

        return $response;
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

        return ApiResponse::json($data);
    }

    public function getActionChecker(string $id, string $checkerId)
    {
        $data = (new Checker\Service)->get($id, $checkerId);

        return ApiResponse::json($data);
    }

    public function createWorkflow()
    {
        $input = Request::all();

        $data = (new Workflow\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function getWorkflow(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function updateWorkflow(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Service)->update($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteWorkflow(string $id)
    {
        $data = (new Workflow\Service)->delete($id);

        return ApiResponse::json($data);
    }

    public function createWorkflowStep(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Step\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getWorkflowStep(string $id, string $stepId)
    {
        $input = Request::all();

        $data = (new Workflow\Step\Service)->get($id, $stepId, $input);

        return ApiResponse::json($data);
    }

    public function postActionComment(string $actionId)
    {
        $input = Request::all();

        $result = (new Comment\Service)->create($actionId, $input);

        return ApiResponse::json($result);
    }

    public function getActionComment(string $actionId)
    {
        $result = (new Comment\Service)->fetchByActionId($actionId);

        return ApiResponse::json($result);
    }

    // Workflow Manager API
    public function getActionsForChecker()
    {
        $data = (new Workflow\Service)->getActionsForChecker();

        return ApiResponse::json($data);
    }

    public function getActionsByMaker()
    {
        $data = (new Workflow\Service)->getActionsByMaker();

        return ApiResponse::json($data);
    }
}
