<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;

use RZP\Models\Workflow;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Comment;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Admin\Org;

class WorkflowController extends Controller
{

    // Not being used
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

    public function getActionMultiple()
    {
        $input = Request::all();

        $data = (new Action\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getActionDetails(string $id)
    {
        $data = (new Action\Service)->getActionDetails($id);

        return ApiResponse::json($data);
    }

    public function postExecuteAction(string $id)
    {
        $response = (new Action\Service)->executeAction($id);

        return $this->getActionDetails($id);
    }

    public function postActionChecker(string $id)
    {
        $input = Request::all();

        $data = (new Checker\Service)->create($id, $input);

        return $this->getActionDetails($id);
    }

    public function closeWorkflowAction(string $id)
    {
        $data = (new Action\Service)->closeAction($id);

        return $this->getActionDetails($id);
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

    public function getActionStates(string $id)
    {
        $data = (new Action\Service)->getStatesOfAction($id);

        return ApiResponse::json($data);
    }

    public function updateWorkflowAction(string $id)
    {
        $input = Request::all();

        $data = (new Action\Service)->updateWorkflowAction($id, $input);

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

        $orgId = $this->ba
                      ->getAdmin()
                      ->getPublicOrgId();

        $data = (new Workflow\Service)->fetch($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getWorkflowMultiple(string $orgId)
    {
        $input = Request::all();

        $data = (new Workflow\Service)->fetchMultiple($orgId, $input);

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

    // Not being used
    public function createWorkflowStep(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Step\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    // Not being used
    public function getWorkflowStep(string $id, string $stepId)
    {
        $data = (new Workflow\Step\Service)->fetch($id, $stepId);

        return ApiResponse::json($data);
    }

    public function getWorkflowSteps(string $id)
    {
        $data = (new Workflow\Step\Service)->fetchMultiple($id);

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


    // Workflow Manager API
    public function getActionsForChecker()
    {
        $data = (new Workflow\Service)->getActionsForChecker();

        return ApiResponse::json($data);
    }

    public function getActionsByMaker()
    {
        $input = Request::all();

        $data = (new Workflow\Service)->getActionsByMakerAndType($input);

        return ApiResponse::json($data);
    }

    public function getActionsChecked()
    {
        $data = (new Workflow\Manager)->getActionsCheckedByAdmin();

        return ApiResponse::json($data);
    }
}
