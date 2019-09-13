<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Services\GovernorService;

class GovernorControllerV1 extends Controller
{
    public function getClients()
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::GET_CLIENTS_V1, $input);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createNamespace($client_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::CREATE_NAMESPACE_V1, $input, $client_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listNamespaces($client_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::LIST_NAMESPACES_V1, $input, $client_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getNamespace($namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::GET_NAMESPACE_V1, $input, null, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateNamespace($client_id, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::UPDATE_NAMESPACE_V1, $input, $client_id, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteNamespace($client, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::DELETE_NAMESPACE_V1, $input, null, $namespace_id, $client);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRuleChains($namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::LIST_RULE_CHAIN_V1, $input, null, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRuleGroups($namespace_id, $rule_chain_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::LIST_RULE_GROUPS_V1, $input, null, $namespace_id, null, $rule_chain_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRuleGroup($namespace_id, $rule_chain_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::CREATE_RULE_GROUP_V1, $input, null, $namespace_id, null, $rule_chain_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRuleGroup($namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::GET_RULE_GROUP_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteRuleGroup($namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::DELETE_RULE_GROUP_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRuleGroup($namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::UPDATE_RULE_GROUP_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRule($namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::CREATE_RULE_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRule($namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::LIST_RULE_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRule($namespace_id, $rule_chain_id, $rule_group_id, $rule_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::GET_RULE_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id, $rule_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteRule($namespace_id, $rule_chain_id, $rule_group_id, $rule_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequestV1(GovernorService::DELETE_RULE_V1, $input, null, $namespace_id, null, $rule_chain_id, $rule_group_id, $rule_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }
}
