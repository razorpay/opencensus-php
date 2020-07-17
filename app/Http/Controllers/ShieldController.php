<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;

class ShieldController extends Controller
{
    const RULES_CREATE_ROUTE = 'shield_rules_create';
    const RULES_UPDATE_ROUTE = 'shield_rules_update';
    const RULES_DELETE_ROUTE = 'shield_rules_delete';

    const LIST_CREATE_ROUTE = 'shield_lists_create';
    const LIST_DELETE_ROUTE = 'shield_lists_delete';

    const LIST_ITEM_BULK_CREATE_ROUTE = 'shield_list_items_add_multiple';
    const LIST_ITEM_DELETE_ROUTE      = 'shield_list_items_delete';
    const LIST_ITEMS_PURGE_ROUTE      = 'shield_list_items_purge';

    const EXTERNAL_SHIELD_ENTITY = 'external_shield_entity';

    const WORKFLOW_APPLICABLE_ROUTES = [
        self::RULES_CREATE_ROUTE,
        self::RULES_UPDATE_ROUTE,
        self::RULES_DELETE_ROUTE,
        self::LIST_CREATE_ROUTE,
        self::LIST_DELETE_ROUTE,
        self::LIST_ITEM_BULK_CREATE_ROUTE,
        self::LIST_ITEM_DELETE_ROUTE,
        self::LIST_ITEMS_PURGE_ROUTE,
    ];

    const EXISTING_ENTITY_RETRIEVAL_ROUTES = [
        self::RULES_UPDATE_ROUTE,
        self::RULES_DELETE_ROUTE,
        self::LIST_DELETE_ROUTE,
        self::LIST_ITEM_DELETE_ROUTE,
    ];

    public function proxyRequest()
    {
        $routeName = Request::route()->getName();

        list($requestUri, $method, $payload) = $this->getProxyRequestDetails();

        // Check if workflow applicable route
        if (in_array($routeName, self::WORKFLOW_APPLICABLE_ROUTES) === true)
        {
            // Fall through
            // 1. If workflow not enabled for permission
            // 2. If Workflow is mocked
            $this->createWorkflowRequestIfApplicable($requestUri, $method, $payload);
        }

        $response = $this->app['shield']->sendRequestV2($requestUri, $method, $payload);

        return ApiResponse::json($response);
    }

    protected function createWorkflowRequestIfApplicable($requestUri, $method, $payload)
    {
        $existingPayload = [];

        $routeName = Request::route()->getName();

        if (in_array($routeName, self::EXISTING_ENTITY_RETRIEVAL_ROUTES) === true)
        {
            $existingPayload = $this->app['shield']->sendRequestV2($requestUri, 'GET', []);
        }

        if (empty($payload) === true)
        {
            $payload = [];
        }

        $this->app['trace']->info(TraceCode::SHIELD_WORKFLOW_REQUEST_INITIATED, [
            'url'    => $requestUri,
            'method' => $method,
            'entity' => [
                'old' => $existingPayload,
                'new' => $payload,
            ],
        ]);

        $metaData = [
            'workflow_meta_data' => [
                'method' => $method,
                'url'    => $requestUri,
            ],
        ];

        // Note: ES fix: This is done as indexing in ES gives an error for key 'expression'
        if (array_key_exists('expression', $payload) === true)
        {
            $payload['_expression'] = $payload['expression'];

            unset($payload['expression']);
        }

        if (array_key_exists('expression', $existingPayload) === true)
        {
            $existingPayload['_expression'] = $existingPayload['expression'];

            unset($existingPayload['expression']);
        }

        Request::replace(array_merge($payload, $metaData));

        // adding additonal info in diff payload
        $payload['_operation_params'] = Request::route()->parameters();

        $this->app['workflow']
            ->setEntityAndId(self::EXTERNAL_SHIELD_ENTITY, $this->getExternalEntityId($routeName))
            ->handle($existingPayload, $payload);

        $this->app['trace']->info(TraceCode::SHIELD_WORKFLOW_REQUEST_SKIPPED, [
            'url'    => $requestUri,
            'method' => $method,
            'entity' => $payload,
        ]);
    }

    protected function getProxyRequestDetails()
    {
        $requestUri = $method = $payload = null;

        // If Workflow triggered call, then extract request details
        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $payload = Request::all();

            $workflowMetaData = $payload['workflow_meta_data'];

            $method = $workflowMetaData['method'];

            $requestUri = $workflowMetaData['url'];

            unset($payload['workflow_meta_data']);

            if (array_key_exists('_expression', $payload) === true)
            {
                $payload['expression'] = $payload['_expression'];

                unset($payload['_expression']);
            }

            $this->app['trace']->info(TraceCode::SHIELD_WORKFLOW_REQUEST_EXECUTED, [
                'url'     => $requestUri,
                'method'  => $method,
                'payload' => $payload,
            ]);
        }
        else
        {
            // v1/shield -> 9 chars
            $requestUri = substr(Request::path(), 9);

            $queryString = Request::getQueryString();

            $method = Request::method();

            if (is_null($queryString) === false)
            {
                $requestUri = $requestUri . '?' . $queryString;
            }

            $payload = Request::json()->all();
        }

        return [$requestUri, $method, $payload];
    }

    protected function getExternalEntityId($routeName)
    {
        $externalEntityId = '';

        switch ($routeName)
        {
            case self::RULES_UPDATE_ROUTE:
                $ruleIdentifiers = Request::route()->parameters();

                array_walk($ruleIdentifiers, function(&$value, $key)
                {
                    $value = $key . '=' . $value;
                });

                $externalEntityId = implode('#', $ruleIdentifiers);

                break;

            default:
                $externalEntityId = substr($this->app['request']->getId(), 0, 12);

                break;
        }

        return $externalEntityId;
    }
}
