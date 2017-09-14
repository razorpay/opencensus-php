<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;

use RZP\Constants\Entity as E;
use RZP\Models\Workflow\Action\Differ;

class WorkflowController extends Controller
{
    public function getActionDiff(string $id)
    {
        $result = (new Differ\Service)->get($id);

        return ApiResponse::json($result);
    }

    public function getActionMultiple()
    {
        $input = Request::all();

        $data = $this->service(E::WORKFLOW_ACTION)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getActionDetails(string $id)
    {
        $data = $this->service(E::WORKFLOW_ACTION)->getActionDetails($id);

        return ApiResponse::json($data);
    }

    public function postExecuteAction(string $id)
    {
        $response = $this->service(E::WORKFLOW_ACTION)->executeAction($id);

        return $this->getActionDetails($id);
    }

    public function postActionChecker(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::ACTION_CHECKER)->create($id, $input);

        return $this->getActionDetails($id);
    }

    public function closeWorkflowAction(string $id)
    {
        $data = $this->service(E::WORKFLOW_ACTION)->closeAction($id);

        return $this->getActionDetails($id);
    }

    public function updateWorkflowAction(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::WORKFLOW_ACTION)->updateWorkflowAction($id, $input);

        return ApiResponse::json($data);
    }

    public function createWorkflow()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function getWorkflow(string $id)
    {
        $input = Request::all();

        $orgId = $this->ba
                      ->getAdmin()
                      ->getPublicOrgId();

        $data = $this->service()->fetch($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getWorkflowMultiple()
    {
        $input = Request::all();

        $orgId = $this->ba
                      ->getAdmin()
                      ->getPublicOrgId();

        $data = $this->service()->fetchMultiple($orgId, $input);

        return ApiResponse::json($data);
    }

    public function updateWorkflow(string $id)
    {
        $input = Request::all();

        $data = $this->service()->update($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteWorkflow(string $id)
    {
        $data = $this->service()->delete($id);

        return ApiResponse::json($data);
    }

    public function postActionComment(string $actionId)
    {
        $input = Request::all();

        $result = $this->service(E::ACTION_COMMENT)->create($actionId, $input);

        return ApiResponse::json($result);
    }
}
