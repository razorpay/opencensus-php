<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Services\GovernorService;

class GovernorController extends Controller
{
    const GET                               = 'GET';
    const PUT                               = 'PUT';
    const POST                              = 'POST';
    const RULES                             = 'rules';
    const DELETE                            = 'DELETE';
    const GOVERNOR_RULE_EDIT_ENTITY         = 'governor_rule_edit';
    const GOVERNOR_RULE_CREATE_ENTITY       = 'governor_rule_create';
    const GOVERNOR_RULE_DELETE_ENTITY       = 'governor_rule_delete';

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
        $input = Request::all();

        $method = Request::method();

        $rawContent = Request::getContent();

        $routeParameters = Request::route()->parameters();

        $path = Request::path() . '?' . Request::getQueryString();

        // handling workflow here
        if (strpos($path, 'w-actions') !== false)
        {
            $this->routeToGovernorViaWorkflow($method, $path, $rawContent);
        }
        else
        {
            $this->routeToWorkflowIfApplicable($method, $path, $routeParameters, $input);
        }

        $response = $this->app['governor']->sendRequestV1($method, $path, $input);

        return ApiResponse::json($response);
    }

    protected function routeToGovernorViaWorkflow(&$method, &$path, $rawContent)
    {
        $rawContentArr = explode('&', $rawContent);

        $content = array();

        foreach ($rawContentArr as $val)
        {
            $tmp = explode('=', $val);

            $content[$tmp[0]] = $tmp[1];
        }

        $method = $content['method'];

        $path = $content['url'];

        $this->app['trace']->info(TraceCode::GOVERNOR_CONTROLLER_WORKFLOW_REQUEST, [
            'path'              => $path,
            'method'            => $method,
            'raw'               => $rawContent,
            'body'              => Request::all(),
            'params'            => Request::route()->parameters(),
        ]);
    }

    protected function routeToWorkflowIfApplicable($method, $path, $routeParameters, $body)
    {
        // checking for rules related url
        if (strpos($path, self::RULES) !== false)
        {
            // Checking for create/edit/delete rules here
            if ($method === self::POST)
            {
                $paramsWithBody = array_merge($routeParameters, $body);

                $this->app['trace']->info(TraceCode::GOVERNOR_CREATE_RULE_REQUEST_VIA_WORKFLOW, $paramsWithBody);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_CREATE_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle([], $paramsWithBody);
            }
            elseif ($method === self::PUT)
            {
                $this->app['trace']->info(TraceCode::GOVERNOR_EDIT_RULE_REQUEST_VIA_WORKFLOW, array_merge($routeParameters, $body));

                $originalRule = array_merge($routeParameters, $body['old_rule']);

                // removing old rule from body, required to populate workflow diff properly
                unset($body['old_rule']);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_EDIT_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle($originalRule, $body);
            }
            elseif ($method === self::DELETE)
            {
                // fetching rule from governor for populating workflow diff
                $rule = $this->app['governor']->sendRequestV1('GET', $path, $body);

                $paramsWithBody = array_merge($routeParameters, $rule ?? []);

                $this->app['trace']->info(TraceCode::GOVERNOR_DELETE_RULE_REQUEST_VIA_WORKFLOW, $paramsWithBody);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_DELETE_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle($paramsWithBody, []);
            }
        }
    }
}
