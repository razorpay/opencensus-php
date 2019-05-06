<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services\GovernorService;

class GovernorController extends Controller
{
    protected $service = GovernorService::class;

    public function createNamespace($source)
    {
        $input = Request::all();

        $response = $this->app['governor']->createNamespace($input, $source);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getDomainModels($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->getDomainModels($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->createDomainModel($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->updateDomainModel($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->createRule($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->createRules($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->updateRule($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->getRules($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->createRuleChain($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }


    public function updateRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->updateRuleChain($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRuleChains($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->getRuleChains($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function executeChains($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->executeChains($input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }
}
