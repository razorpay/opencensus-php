<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Route;
use Illuminate\Routing\Router;
use RZP\Services\GovernorService;

class GovernorController extends Controller
{
    public function createNamespace($source)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_NAMESPACE, $input, $source);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getDomainModels($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::DOMAIN_MODEL_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_DOMAIN_MODEL, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_DOMAIN_MODEL, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULES, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULE, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULES, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::RULE_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRule($source, $namespace, $rulename)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::GET_RULE, $input, $source, $namespace, $rulename);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE_CHAIN, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }


    public function updateRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULE_CHAIN, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRuleChains($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::RULE_CHAIN_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function executeChains($source, $namespace)
    {
        $input = Request::json()->all();

        $queryParams = Request::query();

        $response = $this->app['governor']->sendRequest(GovernorService::EXECUTE_CHAINS, $input, $source, $namespace, null , $queryParams);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function proxy()
    {
        $method = Request::method();

        $path = Request::path() . '?' . Request::getQueryString();

        $content = Request::getContent();

        // Symfony returns each header key as an array.
        $headers  = array_map(function($v) { return current($v); }, Request::header());
        $headers  = array_only($headers, ['content-type']);

        $response = $this->app['governor']->sendRequestV1($method, $path, $content, $headers);

        return ApiResponse::json($response);
    }
}
