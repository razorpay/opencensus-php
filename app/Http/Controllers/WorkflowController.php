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

    public function getActionMultipleInternal(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::WORKFLOW_ACTION)->getActionsByMakerInternal($id, $input);

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

        $result = $this->service(E::COMMENT)->createForWorkflowAction($input, $actionId);

        return ApiResponse::json($result);
    }

    public function getWorkflowPayoutAmountRules()
    {
        $data = $this->service(E::WORKFLOW_PAYOUT_AMOUNT_RULES)->getWorkflowPayoutAmountRules($this->input);

        return ApiResponse::json($data);
    }

    public function editWorkflowPayoutAmountRules()
    {
        $data = $this->service(E::WORKFLOW_PAYOUT_AMOUNT_RULES)->editWorkflowPayoutAmountRules($this->input);

        return ApiResponse::json($data);
    }

    public function getMerchantIdsForCreatePayoutWorkflowPermission()
    {
        $data = $this->service(E::WORKFLOW_PAYOUT_AMOUNT_RULES)->getMerchantIdsForCreatePayoutWorkflowPermission($this->input);

        return ApiResponse::json($data);
    }

    public function postWorkflowPayoutAmountRules()
    {
        $result = $this->service(E::WORKFLOW_PAYOUT_AMOUNT_RULES)->createWorkflowPayoutAmountRules($this->input);

        return ApiResponse::json($result);
    }

    public function getWorkflowObserverData(string $id)
    {
        $data = $this->service()->getWorkflowObserverData($id);

        return ApiResponse::json($data);
    }

    public function updateWorkflowObserverData(string $actionId)
    {
        $result = $this->service()->updateWorkflowObserverData($actionId, $this->input);

        return ApiResponse::json($result);
    }

    public function postNeedClarificationOnWorkflow(string $actionId)
    {
        $input = Request::all();

        $result = $this->service(E::WORKFLOW_ACTION)->needsMerchantClarificationOnWorkflow($actionId, $input);

        return ApiResponse::json($result);
    }

    public function getActionsForRiskAudit(string $merchantId)
    {
        $input = Request::all();

        $result = $this->service(E::WORKFLOW_ACTION)->getActionsForRiskAudit($merchantId, $input);

        return ApiResponse::json($result);
    }
}
