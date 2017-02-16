<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Emi;

class EmiController extends Controller
{
    public function addEmiPlan()
    {
        $input = Request::all();

        $data = (new Emi\Service)->addEmiPlan($input);

        return ApiResponse::json($data);
    }

    public function fetchEmiPlans()
    {
        $data = (new Emi\Service)->all();

        return ApiResponse::json($data);
    }

    public function fetchEmiPlanById($id)
    {
        $data = (new Emi\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function deleteEmiPlan($id)
    {
        $data = (new Emi\Service)->deleteEmiPlan($id);

        return ApiResponse::json($data);
    }

    public function generateEmiExcel()
    {
        $input = Request::all();

        $emiExcel = (new Emi\Service)->getEmiFiles($input);

        return ApiResponse::json($emiExcel);
    }
}
