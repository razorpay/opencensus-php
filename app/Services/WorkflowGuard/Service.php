<?php

namespace RZP\Services\WorkflowGuard;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\Controllers\BaseProxyController;
use RZP\Models\Workflow\Action\OperationType;
use RZP\Models\Workflow\Constants as WorkflowConstants;

class Service extends BaseProxyController
{
    const WORKFLOW_ACTION_SAVE      = 'workflow_action_save';
    const WORKFLOW_ACTION_GET       = 'workflow_action_get';
    const WORKFLOW_SAVE             = 'workflow_save';
    const WORKFLOW_GET              = 'workflow_get';

    const FUNCTION_ROUTE_MAP = [
        'workflow' => [
            'findOrFail'    => self::WORKFLOW_GET,
            'find'          => self::WORKFLOW_GET,
            'saveOrFail'    => self::WORKFLOW_SAVE,
        ],
        'workflow_action' => [
            'findOrFail'        => self::WORKFLOW_ACTION_GET,
            'find'              => self::WORKFLOW_ACTION_GET,
            'saveOrFail'        => self::WORKFLOW_ACTION_SAVE,
            'findByIdAndOrgId'  => self::WORKFLOW_ACTION_GET
        ]
    ];

    const ROUTES_URL_MAP = [
        self::WORKFLOW_GET              => 'rzp.workflow_guard.workflow.v1.WorkflowAPI/Get',
        self::WORKFLOW_SAVE             => 'rzp.workflow_guard.workflow.v1.WorkflowAPI/Upsert',
        self::WORKFLOW_ACTION_GET       => 'rzp.workflow_guard.workflow_action.v1.WorkflowActionAPI/Get',
        self::WORKFLOW_ACTION_SAVE      => 'rzp.workflow_guard.workflow_action.v1.WorkflowActionAPI/Upsert',

    ];
    const MERCHANT_ROUTES = [
        self::WORKFLOW_ACTION_GET,
        self::WORKFLOW_ACTION_SAVE,
        self::WORKFLOW_GET,
        self::WORKFLOW_SAVE,
    ];
    const ADMIN_ROUTES = [];

    const PATH_TIMEOUT_MAP = [];

    public function __construct()
    {
        parent::__construct('workflow_guard');

        $this->trace = $this->app['trace'];

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

        $this->merchantRoutes = self::MERCHANT_ROUTES;

        $this->routesMap = self::ROUTES_URL_MAP;

    }

    /**
     * handleWorkflowGuardRequests will proxy the requests to wfg service for new features - bulk and canary workflow management only for whitelisted workflowIds.
     * This will ensure the traffic is not going to new services for non ramped up workflows.
     * @throws \Exception
     */
    public function handleWorkflowGuardRequests($entity, $repoFunction, $payload, $urlParameters)
    {
        $routeKey = self::FUNCTION_ROUTE_MAP[$entity][$repoFunction] ?? '';

        $currentWorkflowId = $this->app['config']['workflow_guard.current_workflow_id'] ?? '';

        $currentWorkflowDMLAction = $this->app['config']['workflow_guard.current_workflow_dml_action'] ?? '';

        if ($currentWorkflowDMLAction !== 'insert'  &&
            (empty($routeKey) === true ||
            empty($currentWorkflowId) === true||
            ($this->shouldProxyToWFG($currentWorkflowId) === false)))
        {
            return [];
        }

        $this->trace->info(TraceCode::WF_GUARD_PROXY_REQUEST, [
            'routeKey' => $routeKey,
            'payload'  => $payload,
        ]);

        $mock = $this->serviceConfig['mock'];


        if ($mock === true) {
            return $this->workflowGuardMockResponses($routeKey);
        }

        // get path from defined route url map
        $path = self::ROUTES_URL_MAP[$routeKey];

        $headers = $this->getHeadersForDashboardRequest($payload);

        $headers['X-Route-Name'] = $routeKey;

        $this->trace->info(TraceCode::WF_GUARD_PROXY_REQUEST, [
            'routeKey'  => $routeKey,
            'path'      => $path,
        ]);

        $response =  $this->sendRequestAndParseResponse($routeKey, 'POST', $path, $payload, $headers);

        $this->trace->info(TraceCode::WF_GUARD_PROXY_RESPONSE, [
            'response' => $response
        ]);

        return $response;
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    protected function workflowGuardMockResponses(string $routeKey): ?array
    {
        $operationType    = $this->app['config']['workflow_guard.mock.operation_type'] ?? OperationType::SINGLE;
        $canaryPercentage = $this->app['config']['workflow_guard.mock.canary_percentage'] ?? 0;
        $canaryEnabled    = $this->app['config']['workflow_guard.mock.canary_enabled'] ?? false;

        //mocking default response based on RouteKey
        $defaultWorkflowResponse = [
            'workflow' => [
                'id'                        => 'PvFvCDBg1p5Jv4',
                'workflow_id'               => 'PvFvCDBg1p5Jv3',
                'canary_enabled'            => $canaryEnabled,
                'canary_percentage'         => $canaryPercentage,
            ]
        ];
        $defaultWorkflowActionResponse = [
            'workflow_action' => [
                'id'                        => 'PvFvCDBg1p5Jv6',
                'workflow_action_id'        => 'PvFvCDBg1p5Jv5',
                'operation_type'            => $operationType,
                'canary_percentage'         => 0,
                'canary_enabled'            => true,
            ]
        ];
        return match ($routeKey)
        {
            self::WORKFLOW_SAVE, self::WORKFLOW_GET               => $defaultWorkflowResponse,
            self::WORKFLOW_ACTION_SAVE, self::WORKFLOW_ACTION_GET => $defaultWorkflowActionResponse,
            default => null,
        };
    }

    public function shouldProxyToWFG(string $currentWorkflowId): bool
    {
        $mode = 'enable';

        try
        {
            $properties = [
                'id'            => $currentWorkflowId,
                'experiment_id' => $this->app['config']->get(WorkflowConstants::WORKFLOW_WFG_PROXYING_EXPERIMENT_ID),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::WF_GUARD_PROXY_REQUEST_SPLITZ_EXP_CALL, [
                'splitz_output' => $variant,
            ]);

            return $variant === $mode;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::WF_GUARD_PROXY_REQUEST_SPLITZ_EXP_CALL_FAILURE, [
                'current_workflow_id'   => $currentWorkflowId,
                'experiment_id' => $this->app['config']->get(WorkflowConstants::WORKFLOW_WFG_PROXYING_EXPERIMENT_ID) ?? null
            ]);

            return false;
        }
    }
}
