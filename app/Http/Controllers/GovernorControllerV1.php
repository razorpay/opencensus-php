<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Services\GovernorService;

class GovernorControllerV1 extends Controller
{
    public function getClients()
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_NAMESPACE_V1, $input);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createNamespace($source, $client_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_NAMESPACE_V1, $input, $source, null, null, [], $client_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listNamespaces($source, $client_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::LIST_NAMESPACES_V1, $input, $source, null, null, [], $client_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getNamespace($source, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::GET_NAMESPACE_V1, $input, $source, null, null, [], null, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateNamespace($source, $client_id, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_NAMESPACE_V1, $input, $source, null, null, [], $client_id, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteNamespace($source, $client, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::DELETE_NAMESPACE_V1, $input, $source, null, null, [], null, $namespace_id, $client);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRuleChains($source, $namespace_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::LIST_RULE_CHAIN_V1, $input, $source, null, null, [], null, $namespace_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRuleGroups($source, $namespace_id, $rule_chain_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::LIST_RULE_GROUPS_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRuleGroup($source, $namespace_id, $rule_chain_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE_GROUP_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRuleGroup($source, $namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::GET_RULE_GROUP_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteRuleGroup($source, $namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::DELETE_RULE_GROUP_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRule($source, $namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function listRule($source, $namespace_id, $rule_chain_id, $rule_group_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::LIST_RULE_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRule($source, $namespace_id, $rule_chain_id, $rule_group_id, $rule_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::GET_RULE_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id, $rule_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function deleteRule($source, $namespace_id, $rule_chain_id, $rule_group_id, $rule_id)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::DELETE_RULE_V1, $input, $source, null, null, [], null, $namespace_id, null, $rule_chain_id, $rule_group_id, $rule_id);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }
}
