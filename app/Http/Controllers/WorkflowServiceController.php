<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Workflow\Service\Metric;
use RZP\Models\Workflow\Service\StateMap\Service as StateMapService;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;
use RZP\Models\Workflow\Service\Workflow\Service as WorkflowService;

class WorkflowServiceController extends Controller
{
    protected $workflowConfigService;

    protected $stateMapService;

    protected $workflowService;

    public function __construct()
    {
        parent::__construct();

        $this->workflowConfigService = new WorkflowConfigService;

        $this->stateMapService = new StateMapService;

        $this->workflowService = new WorkflowService;
    }

    public function createConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->create($input);

        return response()->json($response);
    }

    public function updateConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->update($input);

        return response()->json($response);
    }

    public function getConfig(string $id)
    {
        $response = $this->workflowConfigService->get($id);

        return response()->json($response);
    }

    public function createWorkflowConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->createWorkflowConfig($input);

        return response()->json($response);
    }

    public function updateWorkflowConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->updateWorkflowConfig($input);

        return response()->json($response);
    }

    public function deleteWorkflowConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->deleteWorkflowConfig($input);

        return response()->json($response);
    }

    public function bulkCreateWorkflowConfig()
    {
        $input = Request::all();

        $response = $this->workflowConfigService->bulkCreateWorkflowConfig($input);

        return response()->json($response);
    }

    public function createWorkflowStateMap()
    {
        $input = Request::all();

        try
        {
            $response = $this->stateMapService->create($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::STATE_MAP_CREATE_VIA_WORKFLOW_SERVICE_FAILED,
                ['input' => $input]);

            // This happens when state callback request is received twice via WFS
            // In that scenario, we won't take action and simply return HTTP 409
            if ($e->getCode() === ErrorCode::BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE)
            {
                $this->trace->count(Metric::WORKFLOW_STATE_MAP_CREATE_DUPLICATE_REQUEST_TOTAL);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS,
                    null,
                    []);
            }

            $this->trace->count(Metric::WORKFLOW_STATE_MAP_CREATE_REQUEST_FAILED_TOTAL);

            throw $e;
        }

        return response()->json($response);
    }

    public function updateWorkflowStateMap(string $id)
    {
        $input = Request::all();

        try
        {
            $response = $this->stateMapService->update($id, $input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::STATE_MAP_UPDATE_VIA_WORKFLOW_SERVICE_FAILED,
                ['id' => $id, 'input' => $input]);

            $this->trace->count(Metric::WORKFLOW_STATE_MAP_UPDATE_REQUEST_FAILED_TOTAL);

            throw $e;
        }

        return response()->json($response);
    }

    public function listWorkflows()
    {
        $input = Request::all();

        $response = $this->workflowService->listWorkflows($input);

        return response()->json($response);
    }

    public function getWorkflow(string $id) {
        $response = $this->workflowService->getWorkflow($id);

        return response()->json($response);
    }

    public function createWorkflowAction() {
        $input = Request::all();

        $response = $this->workflowService->createWorkflowAction($input);

        return response()->json($response);
    }

    public function addWorkflowAssignee() {
        $input = Request::all();

        $response = $this->workflowService->addWorkflowAssignee($input);

        return response()->json($response);
    }

    public function removeWorkflowAssignee() {
        $input = Request::all();

        $response = $this->workflowService->removeWorkflowAssignee($input);

        return response()->json($response);
    }

    public function createComment() {
        $input = Request::all();

        $response = $this->workflowService->createComment($input);

        return response()->json($response);
    }

    public function listComments() {
        $input = Request::all();

        $response = $this->workflowService->listComments($input);

        return response()->json($response);
    }

    public function listCbWorkflows()
    {
        $input = Request::all();

        $response = $this->workflowService->listCbWorkflows($input);

        return response()->json($response);
    }

}
