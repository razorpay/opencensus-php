<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Plan;
use Request;

class PlanController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Plan\Service();
    }

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->service->create($input);

        return ApiResponse::json($plan);
    }
}