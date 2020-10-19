<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Workflow\Service\Metric;
use RZP\Models\Workflow\Client;
use RZP\Models\Workflow\Service\StateMap\Service as StateMapService;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;

class WorkflowServiceController extends Controller
{
    protected $service = Client::class;

    protected $workflowConfigService;

    protected $stateMapService;

    public function __construct()
    {
        parent::__construct();

        $this->workflowConfigService = new WorkflowConfigService;

        $this->stateMapService = new StateMapService;
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

                return ApiResponse::generateResponse($e, 409);
            }
            else
            {
                list($publicError, $httpStatusCode) =
                    ApiResponse::getErrorResponseFields(ErrorCode::BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE);

                return ApiResponse::generateResponse($publicError, $httpStatusCode);
            }
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

            // This happens when state callback request is received twice via WFS
            // In that scenario, we won't take action and simply return HTTP 409
            if ($e->getCode() === ErrorCode::BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE)
            {
                $this->trace->count(Metric::WORKFLOW_STATE_MAP_UPDATE_DUPLICATE_REQUEST_TOTAL);

                return ApiResponse::generateResponse($e, 409);
            }
            else
            {
                list($publicError, $httpStatusCode) =
                    ApiResponse::getErrorResponseFields(ErrorCode::BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE);

                return ApiResponse::generateResponse($publicError, $httpStatusCode);
            }
        }

        return response()->json($response);
    }
}
