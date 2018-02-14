<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ShieldController extends Controller
{
    public function list()
    {
        $response = $this->app['shield']->getRules();

        return ApiResponse::json($response);
    }

    public function get(string $id)
    {
        $response = $this->app['shield']->getRuleById($id);

        return ApiResponse::json($response);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $response = $this->app['shield']->updateRuleById($id, $input);

        return ApiResponse::json($response);
    }

    public function delete(string $id)
    {
        $response = $this->app['shield']->deleteRuleById($id);

        return ApiResponse::json($response);
    }

    public function create()
    {
        $input = Request::all();

        $response = $this->app['shield']->createRule($input);

        return ApiResponse::json($response);
    }

    public function evaluate()
    {
        $input = Request::all();

        $response = $this->app['shield']->evaluateRules($input);

        return ApiResponse::json($response);
    }
}
