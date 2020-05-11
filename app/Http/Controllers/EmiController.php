<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Emi;

class EmiController extends Controller
{
    protected $service = Emi\Service::class;

    public function addEmiPlan()
    {
        $input = Request::all();

        $data = $this->service()->addEmiPlan($input);

        return ApiResponse::json($data);
    }

    public function fetchEmiPlans()
    {
        $data = $this->service()->all();

        return ApiResponse::json($data);
    }

    public function fetchEmiPlanById($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function deleteEmiPlan($id)
    {
        $data = $this->service()->deleteEmiPlan($id);

        return ApiResponse::json($data);
    }

    public function generateEmiExcel()
    {
        $input = Request::all();

        $emiExcel = $this->service()->getEmiFiles($input);

        return ApiResponse::json($emiExcel);
    }

    public function migrateToCardPS()
    {
        $input = Request::all();

        $rows = $this->service()->migratetoCardPS();

        return ApiResponse::json($rows);
    }
}
